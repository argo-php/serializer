<?php

declare(strict_types=1);

namespace Argo\Serializer\Serializer\Chain;

use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Exception\NormalizationException;

final readonly class ChainNormalizer implements NormalizerInterface
{
    use BuiltinTypeTrait;

    private array $normalizers;

    public function __construct(NormalizerInterface ...$normalizers)
    {
        $this->normalizers = $normalizers;
    }

    /**
     * @throws NormalizationException
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): array|string|int|float|bool|object|null {
        $normalizer = $this->getNormalizer($data, $format, $contextBag);

        if ($normalizer !== null) {
            return $normalizer->normalize($data, $format, $contextBag);
        }

        if ($this->isBuiltinType($data)) {
            return $data;
        }

        $pathContext = $contextBag->get(PathContext::class);
        throw new NormalizationException(
            sprintf('Normalization this data is not supported at [%s]', $pathContext->path),
            $pathContext,
        );
    }

    public function supportsNormalization(mixed $data, ?string $format = null, ContextBag $contextBag = new ContextBag()): bool
    {
        return $this->getNormalizer($data, $format, $contextBag) !== null;
    }

    public function getNormalizer(mixed $data, ?string $format, ContextBag $context): ?NormalizerInterface
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supportsNormalization($data, $format, $context)) {
                return $normalizer;
            }
        }

        return null;
    }
}
