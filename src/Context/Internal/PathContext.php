<?php

declare(strict_types=1);

namespace Argo\Serializer\Context\Internal;

use Argo\Serializer\Contract\ContextInterface;

final readonly class PathContext implements ContextInterface
{
    public function __construct(
        public string $path = '',
    ) {}

    public function add(string $item): self
    {
        if ($this->path === '') {
            return new self($item);
        }

        return new self($this->path . '.' . $item);
    }

    public function addPathArray(array $path): self
    {
        return $this->add(implode('.', $path));
    }

    public function item(string|int $index): self
    {
        return new self($this->path . '[' . $index . ']');
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
