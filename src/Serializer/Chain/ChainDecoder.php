<?php

declare(strict_types=1);

namespace Argo\Serializer\Serializer\Chain;

use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\DecoderInterface;
use Argo\Serializer\Exception\UnsupportedFormatException;

final readonly class ChainDecoder implements DecoderInterface
{
    private array $decoders;

    public function __construct(DecoderInterface ...$decoders)
    {
        $this->decoders = $decoders;
    }

    /**
     * @inheritDoc
     */
    public function decode(string $data, string $format, ContextBag $contextBag = new ContextBag()): mixed
    {
        $decoder = $this->getDecoder($format, $contextBag);
        if ($decoder === null) {
            throw new UnsupportedFormatException(sprintf('Decode for the format "%s" is not supported.', $format));
        }

        return $decoder->decode($data, $format, $contextBag);
    }

    public function supportsDecoding(string $format, ContextBag $contextBag = new ContextBag()): bool
    {
        return $this->getDecoder($format, $contextBag) !== null;
    }

    public function getDecoder(string $format, ContextBag $context): ?DecoderInterface
    {
        foreach ($this->decoders as $decoder) {
            if ($decoder->supportsDecoding($format, $context)) {
                return $decoder;
            }
        }

        return null;
    }
}
