<?php

declare(strict_types=1);

namespace Argo\Serializer\ContextFiller;

use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\Serializer\Context\ArgumentContext;
use Argo\Serializer\Context\AttributesContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\DepthContext;
use Argo\Serializer\Context\Internal\PathContext;

final readonly class VariableContextFiller implements VariableContextFillerInterface
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private AttributeContextFillerInterface $attributeContextFiller,
    ) {}

    public function getContextBag(
        string $variableName,
        AttributeCollection $attributes,
        ContextOperationEnum $operation,
        ContextBag $contextBag = new ContextBag(),
    ): ContextBag {
        $variableContextBag = $contextBag->with(
            new ArgumentContext($variableName, [$variableName]),
            new AttributesContext($attributes),
            $contextBag->get(PathContext::class)->add($variableName),
        );

        $variableContextBag = $this->attributeContextFiller->fillContext($variableContextBag, $attributes, $operation);

        $argumentContext = $variableContextBag->get(ArgumentContext::class);

        $pathContext = $contextBag->get(PathContext::class)->addPathArray($argumentContext->normalizedPath);
        $depthContext = $contextBag->get(DepthContext::class)->increase();

        return $variableContextBag->with($pathContext, $depthContext);
    }
}
