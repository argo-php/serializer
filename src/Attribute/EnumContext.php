<?php

declare(strict_types=1);

namespace Argo\Serializer\Attribute;

use Argo\Serializer\Context\BackedEnumContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\ContextFiller\ContextOperationEnum;
use Argo\Serializer\Contract\ContextAttributeInterface;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
readonly class EnumContext implements ContextAttributeInterface
{
    public function __construct(
        private bool $allowInvalidValue = false,
        private mixed $defaultWhenInvalidValue = null,
    ) {}

    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag
    {
        return $contextBag->with(
            $contextBag->get(BackedEnumContext::class)
                ->setAllowInvalidValue($this->allowInvalidValue)
                ->setDefaultWhenInvalidValue($this->defaultWhenInvalidValue),
        );
    }
}
