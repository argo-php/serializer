<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\Types\TypeInterface;
use Argo\Serializer\Context\ContextBag;

/**
 * @api
 */
interface SerializerInterface
{
    public function serialize(mixed $data, string $format, ContextBag $contextBag = new ContextBag()): string;

    /**
     * @template TType
     * @param TypeInterface<TType> $type
     * @return TType
     */
    public function deserialize(string $data, TypeInterface $type, string $format, ContextBag $contextBag = new ContextBag()): mixed;
}
