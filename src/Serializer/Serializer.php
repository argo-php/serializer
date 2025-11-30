<?php

declare(strict_types=1);

namespace Argo\Serializer\Serializer;

use Argo\Serializer\Context\AttributesContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Context\SerializationContext;
use Argo\Serializer\Contract\DecoderAwareInterface;
use Argo\Serializer\Contract\DecoderInterface;
use Argo\Serializer\Contract\DenormalizerAwareInterface;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\EncoderAwareInterface;
use Argo\Serializer\Contract\EncoderInterface;
use Argo\Serializer\Contract\NormalizerAwareInterface;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Contract\SerializerAwareInterface;
use Argo\Serializer\Contract\SerializerInterface;
use Argo\Serializer\Contract\SerializerValidatorInterface;
use Argo\Serializer\Exception\NormalizationException;
use Argo\Serializer\Exception\UnsupportedFormatException;
use Argo\Serializer\Serializer\Chain\ChainDecoder;
use Argo\Serializer\Serializer\Chain\ChainDenormalizer;
use Argo\Serializer\Serializer\Chain\ChainEncoder;
use Argo\Serializer\Serializer\Chain\ChainNormalizer;
use Argo\Types\TypeInterface;

/**
 * @api
 */
readonly class Serializer implements
    SerializerInterface,
    DecoderInterface,
    EncoderInterface,
    NormalizerInterface,
    DenormalizerInterface
{
    private ChainNormalizer $normalizer;
    private ChainDenormalizer $denormalizer;
    private ChainEncoder $encoder;
    private ChainDecoder $decoder;

    public function __construct(
        array $normalizers = [],
        array $encoders = [],
        private ?SerializerValidatorInterface $serializerValidator = null,
    ) {
        $realNormalizers = [];
        $realDenormalizers = [];
        foreach ($normalizers as $normalizer) {
            $this->awareSelf($normalizer);

            if ($normalizer instanceof NormalizerInterface) {
                $realNormalizers[] = $normalizer;
            }
            if ($normalizer instanceof DenormalizerInterface) {
                $realDenormalizers[] = $normalizer;
            }
        }

        $this->normalizer = new ChainNormalizer(...$realNormalizers);
        $this->denormalizer = new ChainDenormalizer(...$realDenormalizers);

        $realEncoders = [];
        $realDecoders = [];
        foreach ($encoders as $encoder) {
            $this->awareSelf($encoder);

            if ($encoder instanceof EncoderInterface) {
                $realEncoders[] = $encoder;
            }
            if ($encoder instanceof DecoderInterface) {
                $realDecoders[] = $encoder;
            }
        }

        $this->encoder = new ChainEncoder(...$realEncoders);
        $this->decoder = new ChainDecoder(...$realDecoders);
    }

    private function awareSelf(object $instance): void
    {
        if ($instance instanceof SerializerAwareInterface) {
            $instance->setSerializer($this);
        }
        if ($instance instanceof NormalizerAwareInterface) {
            $instance->setNormalizer($this);
        }
        if ($instance instanceof DenormalizerAwareInterface) {
            $instance->setDenormalizer($this);
        }
        if ($instance instanceof EncoderAwareInterface) {
            $instance->setEncoder($this);
        }
        if ($instance instanceof DecoderAwareInterface) {
            $instance->setDecoder($this);
        }
    }

    /**
     * @throws UnsupportedFormatException
     */
    public function decode(string $data, string $format, ContextBag $contextBag = new ContextBag()): mixed
    {
        return $this->decoder->decode($data, $format, $contextBag);
    }

    public function supportsDecoding(string $format, ContextBag $contextBag = new ContextBag()): bool
    {
        return $this->decoder->supportsDecoding($format, $contextBag);
    }

    /**
     * @template TType
     * @param TypeInterface<TType> $type
     * @return TType
     */
    public function denormalize(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): mixed {
        if (
            $this->serializerValidator !== null
            && $contextBag->get(SerializationContext::class)->validateBeforeDenormalization
        ) {
            $this->serializerValidator->validate(
                $data,
                $contextBag->get(AttributesContext::class)->attributeCollection,
                $contextBag->get(PathContext::class),
            );
        }

        return $this->denormalizer->denormalize($data, $type, $format, $contextBag);
    }

    public function supportsDenormalization(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return $this->denormalizer->supportsDenormalization($data, $type, $format, $contextBag);
    }

    public function supportsDenormalizationData(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return $this->denormalizer->supportsDenormalizationData($data, $type, $format, $contextBag);
    }

    /**
     * @throws UnsupportedFormatException
     */
    public function encode(mixed $data, string $format, ContextBag $contextBag = new ContextBag()): string
    {
        return $this->encoder->encode($data, $format, $contextBag);
    }

    public function supportsEncoding(string $format, ContextBag $contextBag = new ContextBag()): bool
    {
        return $this->encoder->supportsEncoding($format, $contextBag);
    }

    /**
     * @throws NormalizationException
     */
    public function normalize(
        mixed $data,
        string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): array|string|int|float|bool|object|null {
        return $this->normalizer->normalize($data, $format, $contextBag);
    }

    public function supportsNormalization(mixed $data, string $format = null, ContextBag $contextBag = new ContextBag()): bool
    {
        return $this->normalizer->supportsNormalization($data, $format, $contextBag);
    }

    /**
     * @throws UnsupportedFormatException
     * @throws NormalizationException
     */
    public function serialize(mixed $data, string $format, ContextBag $contextBag = new ContextBag()): string
    {
        if (!$this->supportsEncoding($format, $contextBag)) {
            throw new UnsupportedFormatException(
                sprintf('Serialization for the format "%s" is not supported.', $format),
            );
        }

        $data = $this->normalize($data, $format, $contextBag);

        return $this->encode($data, $format, $contextBag);
    }

    /**
     * @template TType
     * @param TypeInterface<TType> $type
     * @return TType
     * @throws UnsupportedFormatException
     */
    public function deserialize(
        string $data,
        TypeInterface $type,
        string $format,
        ContextBag $contextBag = new ContextBag(),
    ): mixed {
        if (!$this->supportsDecoding($format, $contextBag)) {
            throw new UnsupportedFormatException(
                sprintf('Deserialization for the format "%s" is not supported.', $format),
            );
        }

        $data = $this->decode($data, $format, $contextBag);

        return $this->denormalize($data, $type, $format, $contextBag);
    }

    public function getChainNormalizer(): ChainNormalizer
    {
        return $this->normalizer;
    }

    public function getChainDenormalizer(): ChainDenormalizer
    {
        return $this->denormalizer;
    }

    public function getChainEncoder(): ChainEncoder
    {
        return $this->encoder;
    }

    public function getChainDecoder(): ChainDecoder
    {
        return $this->decoder;
    }
}
