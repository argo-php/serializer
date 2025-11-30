<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\Serializer\Context\ContextBag;
use Argo\Types\TypeInterface;

interface DenormalizerInterface
{
    /**
     * @template TType
     * @param TypeInterface<TType> $type
     * @return TType|null
     */
    public function denormalize(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): mixed;

    public function supportsDenormalization(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool;

    public function supportsDenormalizationData(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool;
}
