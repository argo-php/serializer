<?php

declare(strict_types=1);

namespace Argo\Serializer\Serializer\Chain;

use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\EncoderInterface;
use Argo\Serializer\Exception\UnsupportedFormatException;

final readonly class ChainEncoder implements EncoderInterface
{
    private array $encoders;

    public function __construct(EncoderInterface ...$encoders)
    {
        $this->encoders = $encoders;
    }

    /**
     * @inheritDoc
     */
    public function encode(mixed $data, string $format, ContextBag $contextBag = new ContextBag()): string
    {
        $encoder = $this->getEncoder($format, $contextBag);
        if ($encoder === null) {
            throw new UnsupportedFormatException(sprintf('Encode for the format "%s" is not supported.', $format));
        }

        return $encoder->encode($data, $format, $contextBag);
    }

    public function supportsEncoding(string $format, ContextBag $contextBag = new ContextBag()): bool
    {
        return $this->getEncoder($format, $contextBag) !== null;
    }

    public function getEncoder(string $format, ContextBag $context): ?EncoderInterface
    {
        foreach ($this->encoders as $encoder) {
            if ($encoder->supportsEncoding($format, $context)) {
                return $encoder;
            }
        }

        return null;
    }
}
