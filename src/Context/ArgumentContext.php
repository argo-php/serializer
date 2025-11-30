<?php

declare(strict_types=1);

namespace Argo\Serializer\Context;

use Argo\Serializer\Contract\ContextInterface;

/**
 * @api
 */
readonly class ArgumentContext implements ContextInterface
{
    public function __construct(
        public string $name = '_',
        public array $normalizedPath = [],
        public bool $ignore = false,
        public bool $ignoreIfNull = false,
        public bool $ignoreIfEmpty = false,
        public bool $mutateToArray = false,
        public ?string $serializeTo = null,
    ) {}

    public function setName(string $name): self
    {
        return new self(
            $name,
            $this->normalizedPath,
            $this->ignore,
            $this->ignoreIfNull,
            $this->ignoreIfEmpty,
            $this->mutateToArray,
            $this->serializeTo,
        );
    }

    public function setNormalizedPath(array $normalizedPath): self
    {
        return new self(
            $this->name,
            $normalizedPath,
            $this->ignore,
            $this->ignoreIfNull,
            $this->ignoreIfEmpty,
            $this->mutateToArray,
            $this->serializeTo,
        );
    }

    public function addToNormalizedPath(string $value): self
    {
        $newPath = $this->normalizedPath;
        $newPath[] = $value;

        return new self(
            $this->name,
            $newPath,
            $this->ignore,
            $this->ignoreIfNull,
            $this->ignoreIfEmpty,
            $this->mutateToArray,
            $this->serializeTo,
        );
    }

    public function prependToNormalizedPath(string $value): self
    {
        $newPath = $this->normalizedPath;
        array_unshift($newPath, $value);

        return new self(
            $this->name,
            $newPath,
            $this->ignore,
            $this->ignoreIfNull,
            $this->ignoreIfEmpty,
            $this->mutateToArray,
            $this->serializeTo,
        );
    }

    public function setIgnore(bool $ignore): self
    {
        return new self(
            $this->name,
            $this->normalizedPath,
            $ignore,
            $this->ignoreIfNull,
            $this->ignoreIfEmpty,
            $this->mutateToArray,
            $this->serializeTo,
        );
    }

    public function setIgnoreIfNull(bool $ignoreIfNull): self
    {
        return new self(
            $this->name,
            $this->normalizedPath,
            $this->ignore,
            $ignoreIfNull,
            $this->ignoreIfEmpty,
            $this->mutateToArray,
            $this->serializeTo,
        );
    }

    public function setIgnoreIfEmpty(bool $ignoreIfEmpty): self
    {
        return new self(
            $this->name,
            $this->normalizedPath,
            $this->ignore,
            $this->ignoreIfNull,
            $ignoreIfEmpty,
            $this->mutateToArray,
            $this->serializeTo,
        );
    }

    public function setMutateToArray(bool $mutateToArray): self
    {
        return new self(
            $this->name,
            $this->normalizedPath,
            $this->ignore,
            $this->ignoreIfNull,
            $this->ignoreIfEmpty,
            $mutateToArray,
            $this->serializeTo,
        );
    }

    public function setSerializeTo(?string $serializeTo): self
    {
        return new self(
            $this->name,
            $this->normalizedPath,
            $this->ignore,
            $this->ignoreIfNull,
            $this->ignoreIfEmpty,
            $this->mutateToArray,
            $serializeTo,
        );
    }
}
