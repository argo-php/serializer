<?php

declare(strict_types=1);

namespace Argo\Serializer\Normalizer;

use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\EntityDefinition\Definition\ClassDefinition;
use Argo\EntityDefinition\Definition\ParameterDefinition;
use Argo\EntityDefinition\Definition\PropertyDefinition;
use Argo\EntityDefinition\Reflector\ClassDefinition\ClassDefinitionReflectorInterface;
use Argo\Serializer\Aware\DecoderAwareTrait;
use Argo\Serializer\Aware\DenormalizerAwareTrait;
use Argo\Serializer\Aware\EncoderAwareTrait;
use Argo\Serializer\Aware\NormalizerAwareTrait;
use Argo\Serializer\Context\ArgumentContext;
use Argo\Serializer\Context\AttributesContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\DepthContext;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Context\Internal\ReferencesContext;
use Argo\Serializer\Context\SerializationContext;
use Argo\Serializer\ContextFiller\ContextOperationEnum;
use Argo\Serializer\ContextFiller\VariableContextFillerInterface;
use Argo\Serializer\Contract\DecoderAwareInterface;
use Argo\Serializer\Contract\DenormalizerAwareInterface;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\DiscriminatorEnricherInterface;
use Argo\Serializer\Contract\DiscriminatorResolverInterface;
use Argo\Serializer\Contract\EncoderAwareInterface;
use Argo\Serializer\Contract\NormalizerAwareInterface;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\DataHelper;
use Argo\Serializer\Exception\CircularReferenceException;
use Argo\Serializer\Exception\InvalidArgumentException;
use Argo\Serializer\Exception\InvalidDataTypeException;
use Argo\Serializer\Exception\SkipPropertyNormalizationException;
use Argo\Serializer\Exception\Validation\IncorrectTypeException;
use Argo\Serializer\Exception\Validation\RequiredException;
use Argo\Serializer\Exception\ValidationBagException;
use Argo\Serializer\Exception\ValidationException;
use Argo\Serializer\Normalizer\ObjectNormalizer\NoValue;
use Argo\Types\Atomic\ClassType;
use Argo\Types\Atomic\ObjectType;
use Argo\Types\TypeInterface;

/**
 * @api
 */
class ObjectNormalizer implements
    NormalizerInterface,
    DenormalizerInterface,
    NormalizerAwareInterface,
    DenormalizerAwareInterface,
    DecoderAwareInterface,
    EncoderAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;
    use DecoderAwareTrait;
    use EncoderAwareTrait;

    public function __construct(
        private readonly ClassDefinitionReflectorInterface $classDefinitionReflector,
        private readonly DiscriminatorResolverInterface $discriminatorResolver,
        private readonly DiscriminatorEnricherInterface $discriminatorEnricher,
        private readonly VariableContextFillerInterface $variableContextFiller,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws IncorrectTypeException
     * @throws ValidationBagException
     * @throws ValidationException
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public function denormalize(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): object {
        if (!$type instanceof ClassType || (!class_exists($type->className) && !interface_exists($type->className))) {
            throw new InvalidArgumentException(
                sprintf('The property must have class type, actual: [%s]', $type),
            );
        }

        if (!$this->supportsDenormalizationData($data, $type, $format, $contextBag)) {
            throw new IncorrectTypeException(
                $contextBag->get(PathContext::class),
                'object|array',
                get_debug_type($data),
            );
        }

        if (is_object($data)) {
            $data = (array) $data;
        }

        $reflection = new \ReflectionClass($type->className);
        $classDefinition = $this->classDefinitionReflector->getClassDefinition($reflection);

        if ($classDefinition->isAbstract() || interface_exists($type->className)) {
            $resolvedType = $this->discriminatorResolver->resolve(
                $type,
                $data,
                $this->getDenormalizer(),
                $contextBag->get(AttributesContext::class)->attributeCollection,
                $contextBag->get(PathContext::class),
            );

            if (!$resolvedType instanceof ClassType || !class_exists($resolvedType->className)) {
                throw new InvalidArgumentException(
                    sprintf('The property must have class type, actual: [%s]', $type),
                );
            }

            $reflection = new \ReflectionClass($resolvedType->className);
            $classDefinition = $this->classDefinitionReflector->getClassDefinition($reflection);
        }

        /** @psalm-suppress InvalidArgument */
        return $this->getInstance($data, $classDefinition, $contextBag, $format);
    }

    /**
     * @template TClass
     * @param ClassDefinition<TClass> $classDefinition
     * @return TClass
     *
     * @throws ValidationBagException
     */
    private function getInstance(
        array $data,
        ClassDefinition $classDefinition,
        ContextBag $context,
        ?string $format = null,
    ): object {
        $constructorArguments = [];
        $errorsBag = new ValidationBagException();

        $constructorDefinition = $classDefinition->methods->getConstructor();
        if ($constructorDefinition !== null) {
            foreach ($constructorDefinition->parameters as $parameter) {
                try {
                    $attributes = $classDefinition->attributes->merge($parameter->attributes);
                    $parameterContextBag = $this->variableContextFiller->getContextBag(
                        $parameter->name,
                        $attributes,
                        ContextOperationEnum::Denormalization,
                        $context,
                    );
                    $constructorArguments[] = $this->prepareValue($data, $parameter, $format, $parameterContextBag);
                } catch (ValidationException|ValidationBagException $exception) {
                    if ($context->get(SerializationContext::class)->stopOnFirstValidationError) {
                        throw $exception;
                    }

                    $errorsBag->addException($exception);
                } catch (SkipPropertyNormalizationException) {
                    continue;
                }
            }
        }

        if (!$errorsBag->empty()) {
            throw $errorsBag;
        }

        $instance = new ($classDefinition->className)(...$constructorArguments);

        try {
            $this->fillProperties($instance, $classDefinition, $data, $context, $format);
        } catch (ValidationException|ValidationBagException $exception) {
            $errorsBag->addException($exception);
        }

        if (!$errorsBag->empty()) {
            throw $errorsBag;
        }

        return $instance;
    }

    /**
     * @throws ValidationBagException
     */
    private function fillProperties(
        object $instance,
        ClassDefinition $classDefinition,
        array $data,
        ContextBag $contextBag,
        ?string $format = null,
    ): void {
        if ($classDefinition->isReadonly()) {
            return;
        }

        $errorsBag = new ValidationBagException();
        foreach ($classDefinition->properties as $property) {
            try {
                if ($property->isReadOnly() || $property->isPromoted() || !$property->isPublic()) {
                    continue;
                }

                $attributes = $classDefinition->attributes->merge($property->attributes);
                $propertyContextBag = $this->variableContextFiller->getContextBag(
                    $property->name,
                    $attributes,
                    ContextOperationEnum::Denormalization,
                    $contextBag,
                );

                $propertyName = $property->name;
                $instance->$propertyName = $this->prepareValue($data, $property, $format, $propertyContextBag, true);
            } catch (ValidationException|ValidationBagException $exception) {
                if ($contextBag->get(SerializationContext::class)->stopOnFirstValidationError) {
                    throw $exception;
                }

                $errorsBag->addException($exception);
            } catch (SkipPropertyNormalizationException) {
                continue;
            }
        }

        if (!$errorsBag->empty()) {
            throw $errorsBag;
        }
    }

    /**
     * @throws SkipPropertyNormalizationException
     * @throws RequiredException
     */
    private function prepareValue(
        array $data,
        ParameterDefinition|PropertyDefinition $definition,
        ?string $format,
        ContextBag $propertyContextBag,
        bool $skipNoValue = false,
    ): mixed {
        $argumentContext = $propertyContextBag->get(ArgumentContext::class);
        $pathContext = $propertyContextBag->get(PathContext::class);

        if ($format === 'xml') {
            if (!array_key_exists('#', $data)) {
                $data = ['#' => $data];
            }
            if (
                DataHelper::head($argumentContext->normalizedPath) !== '#'
                && !str_starts_with(DataHelper::head($argumentContext->normalizedPath), '@')
            ) {
                $argumentContext = $argumentContext->prependToNormalizedPath('#');
            }
        }

        if (count($argumentContext->normalizedPath) !== 0) {
            $argumentData = DataHelper::get($data, $argumentContext->normalizedPath, new NoValue());
        } else {
            $argumentData = new NoValue();
        }

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (
            $argumentContext->ignore
            || ($argumentContext->ignoreIfNull && $argumentData === null)
            || ($argumentContext->ignoreIfEmpty && empty($argumentData))
        ) {
            $argumentData = new NoValue();
        }

        if (!$argumentData instanceof NoValue) {
            if ($argumentContext->serializeTo !== null && is_string($argumentData)) {
                $argumentData = $this->getDecoder()->decode($argumentData, $argumentContext->serializeTo, $propertyContextBag);
            }

            if ($argumentContext->mutateToArray && (!is_array($argumentData) || !array_is_list($argumentData))) {
                $argumentData = [$argumentData];
            }

            return $this->getDenormalizer()->denormalize(
                $argumentData,
                $definition->type,
                $format,
                $propertyContextBag,
            );
        } elseif ($definition->hasDefaultValue) {
            return $definition->defaultValue;
        } elseif ($skipNoValue) {
            throw new SkipPropertyNormalizationException();
        } else {
            throw new RequiredException($pathContext);
        }
    }

    public function supportsDenormalization(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return $type instanceof ClassType && (class_exists($type->className) || interface_exists($type->className));
    }

    public function supportsDenormalizationData(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return is_object($data) || is_array($data);
    }

    /**
     * @throws \ReflectionException
     * @throws InvalidDataTypeException
     * @throws ValidationBagException
     * @throws CircularReferenceException
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): object|array|null {
        if (!is_object($data)) {
            throw new InvalidDataTypeException($data, new ObjectType());
        }

        $serializationContext = $contextBag->get(SerializationContext::class);
        $referencesContext = $contextBag->get(ReferencesContext::class);

        if ($serializationContext->circularReferenceLimit < 1) {
            $circularReferenceLimit = 1;
        } else {
            $circularReferenceLimit = $serializationContext->circularReferenceLimit;
        }

        if ($referencesContext->getReferenceNumber($data) >= $circularReferenceLimit) {
            if ($serializationContext->throwOnCircularReference) {
                throw new CircularReferenceException($contextBag->get(PathContext::class));
            } else {
                return null;
            }
        } else {
            $contextBag = $contextBag->with($referencesContext->addReferenceCall($data));
        }

        if ($data::class === \stdClass::class) {
            $result = $this->normalizeStdClass($data, $format, $contextBag);
        } else {
            $result = $this->normalizeClass($data, $format, $contextBag);
        }

        if (!$serializationContext->normalizeAsArray) {
            $result = (object) $result;
        }

        return $result;
    }

    /**
     * @throws ValidationBagException
     */
    private function normalizeStdClass(\stdClass $data, ?string $format, ContextBag $contextBag): array
    {
        $result = [];

        $reflection = new \ReflectionObject($data);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $errors = new ValidationBagException();
        foreach ($properties as $property) {
            try {
                $propertyContextBag = $this->variableContextFiller->getContextBag(
                    $property->getName(),
                    new AttributeCollection(),
                    ContextOperationEnum::Normalization,
                    $contextBag,
                );
                $argumentContext = $propertyContextBag->get(ArgumentContext::class);

                try {
                    $value = $this->normalizeProperty($data, $format, $property, $propertyContextBag);
                } catch (SkipPropertyNormalizationException) {
                    continue;
                }

                if (count($argumentContext->normalizedPath) !== 0) {
                    DataHelper::set($result, $argumentContext->normalizedPath, $value);
                }
            } catch (ValidationException|ValidationBagException $exception) {
                if ($contextBag->get(SerializationContext::class)->stopOnFirstValidationError) {
                    throw $exception;
                }

                $errors->addException($exception);
            }
        }

        if (!$errors->empty()) {
            throw $errors;
        }

        return $result;
    }

    /**
     * @throws \ReflectionException
     * @throws ValidationBagException
     */
    private function normalizeClass(object $data, ?string $format, ContextBag $contextBag): array
    {
        $result = [];

        $reflection = new \ReflectionObject($data);
        $classDefinition = $this->classDefinitionReflector->getClassDefinition($reflection);

        $errorsBag = new ValidationBagException();
        foreach ($classDefinition->properties as $property) {
            if (!$property->isPublic()) {
                continue;
            }

            try {
                $attributes = $classDefinition->attributes->merge($property->attributes);
                $propertyContextBag = $this->variableContextFiller->getContextBag(
                    $property->name,
                    $attributes,
                    ContextOperationEnum::Normalization,
                    $contextBag,
                );
                $argumentContext = $propertyContextBag->get(ArgumentContext::class);

                try {
                    $value = $this->normalizeProperty(
                        $data,
                        $format,
                        $reflection->getProperty($property->name),
                        $propertyContextBag,
                    );
                } catch (SkipPropertyNormalizationException) {
                    continue;
                }

                if (count($argumentContext->normalizedPath) !== 0) {
                    DataHelper::set($result, $argumentContext->normalizedPath, $value);
                }
            } catch (ValidationException|ValidationBagException $exception) {
                if ($contextBag->get(SerializationContext::class)->stopOnFirstValidationError) {
                    throw $exception;
                }

                $errorsBag->addException($exception);
            }
        }

        if (!$errorsBag->empty()) {
            throw $errorsBag;
        }

        return $this->discriminatorEnricher->enrich(
            $data,
            $result,
            $contextBag->get(AttributesContext::class)->attributeCollection,
        );
    }

    /**
     * @throws SkipPropertyNormalizationException
     */
    private function normalizeProperty(
        object $object,
        ?string $format,
        \ReflectionProperty $reflectionProperty,
        ContextBag $propertyContextBag,
    ): mixed {
        $serializationContext = $propertyContextBag->get(SerializationContext::class);
        $depthContext = $propertyContextBag->get(DepthContext::class);
        $argumentContext = $propertyContextBag->get(ArgumentContext::class);

        $propertyValue = $reflectionProperty->getValue($object);

        if (
            $argumentContext->ignore
            || ($argumentContext->ignoreIfNull && $propertyValue === null)
            || ($argumentContext->ignoreIfEmpty && empty($propertyValue))
        ) {
            throw new SkipPropertyNormalizationException();
        }

        if (
            $serializationContext->serializationDepth !== false
            && $depthContext->depth > $serializationContext->serializationDepth
        ) {
            $propertyValue = null;
        } else {
            $propertyValue = $this->getNormalizer()->normalize($propertyValue, $format, $propertyContextBag);
            if ($argumentContext->serializeTo !== null) {
                $propertyValue = $this->getEncoder()->encode($propertyValue, $argumentContext->serializeTo, $propertyContextBag);
            }
        }

        return $propertyValue;
    }

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return is_object($data);
    }
}
