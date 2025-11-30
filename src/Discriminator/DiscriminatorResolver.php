<?php

declare(strict_types=1);

namespace Argo\Serializer\Discriminator;

use Argo\AttributeCollector\Contract\AttributeManagerInterface;
use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\EntityDefinition\TypeReflector\VariableTypeReflectorInterface;
use Argo\Serializer\Attribute\Discriminator;
use Argo\Serializer\Attribute\DiscriminatorMap;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\DiscriminatorResolverInterface;
use Argo\Serializer\Exception\Validation\IncorrectTypeException;
use Argo\Serializer\Exception\Validation\RequiredException;
use Argo\Serializer\Exception\Validation\UnexpectedEnumValueException;
use Argo\Serializer\Exception\ValidationException;
use Argo\Types\Atomic\ClassType;
use Argo\Types\Complex\IntersectType;
use Argo\Types\Complex\UnionType;
use Argo\Types\NamedTypeInterface;
use Argo\Types\TypeInterface;

final readonly class DiscriminatorResolver implements DiscriminatorResolverInterface
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private VariableTypeReflectorInterface $variableTypeReflector,
        private AttributeManagerInterface $attributeManager,
    ) {}

    public function resolve(
        TypeInterface $targetType,
        mixed $value,
        DenormalizerInterface $denormalizer,
        AttributeCollection $attributeCollection = new AttributeCollection(),
        PathContext $pathContext = new PathContext(),
    ): NamedTypeInterface {
        $valueType = $this->variableTypeReflector->getVariableType($value);

        $suitableType = $this->tryResolveViaCustomDiscriminatorResolver(
            $targetType,
            $value,
            $attributeCollection,
            $pathContext,
            $denormalizer,
        );
        if ($suitableType !== null) {
            return $suitableType;
        }

        if ($targetType instanceof UnionType || $targetType instanceof IntersectType) {
            $attribute = $attributeCollection->firstByType(DiscriminatorMap::class);
            if ($attribute !== null) {
                $suitableType = $this->tryResolveValueViaDiscriminatorMap($attribute, $value, $pathContext);

                if ($suitableType !== null) {
                    return $suitableType;
                }
            }
        }

        if ($targetType instanceof UnionType) {
            $suitableTypes = [];
            foreach ($targetType->types as $type) {
                if ($type instanceof NamedTypeInterface && $type->isContravariantTo($valueType)) {
                    return $type;
                }

                try {
                    $suitableTypes[] = $this->resolve($type, $value, $denormalizer, $attributeCollection, $pathContext);
                } catch (\Throwable) {
                    continue;
                }
            }

            if (count($suitableTypes) > 0) {
                return $suitableTypes[0];
            }
        }

        if ($targetType instanceof NamedTypeInterface) {
            $suitableType = $this->tryResolveNamedType(
                $targetType,
                $value,
                $attributeCollection,
                $pathContext,
                $denormalizer,
            );
            if ($suitableType !== null) {
                return $suitableType;
            }
        }

        throw new IncorrectTypeException($pathContext, $targetType, $valueType);
    }

    /**
     * @throws UnexpectedEnumValueException
     * @throws RequiredException
     */
    private function tryResolveNamedType(
        NamedTypeInterface $targetType,
        mixed $value,
        AttributeCollection $attributeCollection,
        PathContext $pathContext,
        DenormalizerInterface $denormalizer,
    ): ?NamedTypeInterface {
        if ($targetType instanceof ClassType && !$this->targetIsInstantiable($targetType->className)) {
            $attribute = $attributeCollection->firstByType(DiscriminatorMap::class);
            if ($attribute === null) {
                $attribute = $this->attributeManager->getAttributesForClass($targetType->className)
                    ->firstByType(DiscriminatorMap::class);
            }
            if ($attribute !== null) {
                return $this->tryResolveValueViaDiscriminatorMap($attribute, $value, $pathContext);
            }
        }

        if ($denormalizer->supportsDenormalizationData($value, $targetType)) {
            return $targetType;
        }

        return null;
    }

    /**
     * @param class-string $className
     */
    private function targetIsInstantiable(string $className): bool
    {
        try {
            $reflection = new \ReflectionClass($className);
        } catch (\ReflectionException) {
            return true;
        }

        return $reflection->isInstantiable();
    }

    /**
     * @throws UnexpectedEnumValueException
     * @throws RequiredException
     */
    private function tryResolveValueViaDiscriminatorMap(
        DiscriminatorMap $attribute,
        mixed $value,
        PathContext $pathContext,
    ): ?NamedTypeInterface {
        if (!is_array($value)) {
            return null;
        }

        if (!array_key_exists($attribute->fieldName, $value)) {
            if ($attribute->defaultClassName !== null) {
                return new ClassType($attribute->defaultClassName);
            }

            throw new RequiredException($pathContext->add($attribute->fieldName));
        }

        $typeFieldValue = $value[$attribute->fieldName];
        if (!array_key_exists($typeFieldValue, $attribute->map)) {
            if ($attribute->defaultClassName !== null) {
                return new ClassType($attribute->defaultClassName);
            }

            throw new UnexpectedEnumValueException(
                $pathContext->add($attribute->fieldName),
                array_keys($attribute->map),
            );
        }

        return new ClassType($attribute->map[$typeFieldValue]);
    }

    /**
     * @throws ValidationException
     */
    private function tryResolveViaCustomDiscriminatorResolver(
        TypeInterface $targetType,
        mixed $value,
        AttributeCollection $attributeCollection,
        PathContext $pathContext,
        DenormalizerInterface $denormalizer,
    ): ?NamedTypeInterface {
        $attribute = $attributeCollection->firstByType(Discriminator::class);
        if ($attribute === null && $targetType instanceof ClassType) {
            $attribute = $this->attributeManager->getAttributesForClass($targetType->className)
                ->firstByType(Discriminator::class);
        }
        if ($attribute === null) {
            return null;
        }

        $discriminatorResolver = $attribute->discriminatorResolver;
        if ($discriminatorResolver::class === self::class) {
            return null;
        }

        return $discriminatorResolver?->resolve($targetType, $value, $denormalizer, $attributeCollection, $pathContext);
    }
}
