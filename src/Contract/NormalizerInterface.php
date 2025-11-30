<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\Serializer\Context\ContextBag;

interface NormalizerInterface
{
    public function normalize(
        mixed $data,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): array|string|int|float|bool|object|null;

    public function supportsNormalization(mixed $data, ?string $format = null, ContextBag $contextBag = new ContextBag()): bool;
}
