<?php

declare(strict_types=1);

namespace Argo\Serializer\ParametersMapper;

use Argo\EntityDefinition\Reflector\MethodDefinition\MethodDefinitionReflectorInterface;
use Argo\Serializer\ContextFiller\ContextOperationEnum;
use Argo\Serializer\ContextFiller\VariableContextFillerInterface;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Exception\Validation\RequiredException;
use Argo\Serializer\Exception\ValidationBagException;
use Argo\Serializer\Exception\ValidationException;

/**
 * @api
 */
readonly class ParametersMapper implements ParametersMapperInterface
{
    public function __construct(
        private MethodDefinitionReflectorInterface $methodDefinitionReflector,
        private DenormalizerInterface $serializer,
        private VariableContextFillerInterface $variableContextFiller,
    ) {}

    /**
     * @throws ValidationBagException
     */
    public function parseMethodParameters(\ReflectionMethod $reflectionMethod, array $arguments): array
    {
        $methodDefinition = $this->methodDefinitionReflector->getMethodDefinition($reflectionMethod);

        $result = [];

        $validationBag = new ValidationBagException();

        foreach ($methodDefinition->parameters as $parameter) {
            try {
                if (array_key_exists($parameter->name, $arguments)) {
                    $contextBag = $this->variableContextFiller->getContextBag(
                        $parameter->name,
                        $parameter->attributes,
                        ContextOperationEnum::Denormalization,
                    );

                    $result[] = $this->serializer->denormalize(
                        $arguments[$parameter->name],
                        $parameter->type,
                        'json',
                        $contextBag,
                    );
                } elseif ($parameter->hasDefaultValue) {
                    $result[] = $parameter->defaultValue;
                } else {
                    throw new RequiredException($parameter->name);
                }
            } catch (ValidationException|ValidationBagException $e) {
                $validationBag->addException($e);
            }
        }

        if (!$validationBag->empty()) {
            throw $validationBag;
        }

        return $result;
    }
}
