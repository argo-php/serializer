<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\Serializer\Context\ContextBag;

/**
 * @api
 */
interface DenormalizableInterface
{
    public function denormalize(
        DenormalizerInterface $denormalizer,
        mixed $data,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): void;
}
