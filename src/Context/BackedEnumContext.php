<?php

declare(strict_types=1);

namespace Argo\Serializer\Context;

use Argo\Serializer\Contract\ContextInterface;

/**
 * @api
 */
readonly class BackedEnumContext implements ContextInterface
{
    public function __construct(
        public bool $allowInvalidValue = false,
        public mixed $defaultWhenInvalidValue = null,
    ) {}

    public function setAllowInvalidValue(bool $allowInvalidValue): self
    {
        return new self($allowInvalidValue, $this->defaultWhenInvalidValue);
    }

    public function setDefaultWhenInvalidValue(mixed $value): self
    {
        return new self($this->allowInvalidValue, $value);
    }
}
