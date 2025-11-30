<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\Serializer\Exception\SerializerException;
use Argo\Types\TypeInterface;
use Argo\Serializer\Context\ContextBag;

/**
 * @api
 */
interface SerializerInterface
{
    /**
     * @throws SerializerException
     */
    public function serialize(mixed $data, string $format, ContextBag $contextBag = new ContextBag()): string;

    /**
     * @template TType
     * @param TypeInterface<TType> $type
     * @return TType
     * @throws SerializerException
     */
    public function deserialize(string $data, TypeInterface $type, string $format, ContextBag $contextBag = new ContextBag()): mixed;
}
