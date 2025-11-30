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
class ArrayableNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @throws InvalidArgumentException
     * @throws NormalizationException
     */
    public function normalize(mixed $data, string $format = null, ContextBag $contextBag = new ContextBag()): array|string|int|float|bool|object|null
    {
        /** @psalm-suppress ArgumentTypeCoercion,UndefinedClass */
        if (
            !is_a($data, 'Hyperf\Contract\Arrayable')
            && !is_a($data, 'Illuminate\Contracts\Support\Arrayable')
        ) {
            throw new InvalidArgumentException('The object must implements "Arrayable" interface.');
        }

        /** @psalm-suppress UndefinedClass */
        return $this->getNormalizer()->normalize($data->toArray(), $format, $contextBag);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, ContextBag $contextBag = new ContextBag()): bool
    {
        /** @psalm-suppress ArgumentTypeCoercion,UndefinedClass */
        return is_a($data, 'Hyperf\Contract\Arrayable')
            || is_a($data, 'Illuminate\Contracts\Support\Arrayable');
    }
}
