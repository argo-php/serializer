<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\Serializer\Context\ContextBag;

interface NormalizableInterface
{
    public function normalize(
        NormalizerInterface $normalizer,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): array|string|int|float|bool|object|null;
}
