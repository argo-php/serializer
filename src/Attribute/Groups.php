<?php

declare(strict_types=1);

namespace Argo\Serializer\Attribute;

use Argo\Serializer\Context\ArgumentContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\SerializationContext;
use Argo\Serializer\ContextFiller\ContextOperationEnum;
use Argo\Serializer\Contract\ContextAttributeInterface;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
readonly class Groups implements ContextAttributeInterface
{
    private array $groups;

    public function __construct(string ...$groups)
    {
        $this->groups = $groups;
    }

    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag
    {
        $group = $contextBag->get(SerializationContext::class)->group;

        if (in_array($group, $this->groups, true)) {
            return $contextBag;
        }

        return $contextBag->with(
            $contextBag->get(ArgumentContext::class)->setIgnore(true),
        );
    }
}
