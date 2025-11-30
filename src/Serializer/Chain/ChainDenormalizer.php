<?php

declare(strict_types=1);

namespace Argo\Serializer\Serializer\Chain;

use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Exception\DenormalizationException;
use Argo\Types\TypeInterface;

final readonly class ChainDenormalizer implements DenormalizerInterface
{
    use BuiltinTypeTrait;

    private array $denormalizers;

    public function __construct(DenormalizerInterface ...$denormalizers)
    {
        $this->denormalizers = $denormalizers;
    }

    /**
     * @throws DenormalizationException
     */
    public function denormalize(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): mixed {
        $denormalizer = $this->getDenormalizer($data, $type, $format, $contextBag);

        if ($denormalizer !== null) {
            return $denormalizer->denormalize($data, $type, $format, $contextBag);
        }

        if ($this->isBuiltinType($data)) {
            return $data;
        }

        $pathContext = $contextBag->get(PathContext::class);
        throw new DenormalizationException(
            sprintf('Denormalization this data is not supported at [%s]', $pathContext->path),
            $pathContext,
        );
    }

    public function supportsDenormalization(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return $this->getDenormalizer($data, $type, $format, $contextBag) !== null;
    }

    public function supportsDenormalizationData(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        foreach ($this->denormalizers as $denormalizer) {
            if (
                $denormalizer->supportsDenormalization($data, $type, $format, $contextBag)
                && $denormalizer->supportsDenormalizationData($data, $type, $format, $contextBag)
            ) {
                return true;
            }
        }

        return false;
    }

    public function getDenormalizer(
        mixed $data,
        TypeInterface $type,
        ?string $format,
        ContextBag $context,
    ): ?DenormalizerInterface {
        foreach ($this->denormalizers as $denormalizer) {
            if ($denormalizer->supportsDenormalization($data, $type, $format, $context)) {
                return $denormalizer;
            }
        }

        return null;
    }
}
