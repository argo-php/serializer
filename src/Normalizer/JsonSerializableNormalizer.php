<?php

declare(strict_types=1);

namespace Argo\Serializer\Normalizer;

use Argo\Serializer\Aware\NormalizerAwareTrait;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\NormalizerAwareInterface;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Exception\InvalidArgumentException;
use Argo\Serializer\Exception\NormalizationException;

/**
 * @api
 */
class JsonSerializableNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @throws InvalidArgumentException
     * @throws NormalizationException
     */
    public function normalize(
        mixed      $data,
        ?string    $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): array|string|int|float|bool|object|null {
        if (!$data instanceof \JsonSerializable) {
            throw new InvalidArgumentException(sprintf('The object must implement "%s".', \JsonSerializable::class));
        }

        return $this->getNormalizer()->normalize($data->jsonSerialize(), $format, $contextBag);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, ContextBag $contextBag = new ContextBag()): bool
    {
        return $data instanceof \JsonSerializable;
    }
}
