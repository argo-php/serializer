<?php

declare(strict_types=1);

namespace Argo\Serializer\Context\Internal;

use Argo\Serializer\Contract\ContextInterface;

final readonly class DepthContext implements ContextInterface
{
    public function __construct(
        public int $depth = 0,
    ) {}

    public function increase(): self
    {
        return new self($this->depth + 1);
    }
}
