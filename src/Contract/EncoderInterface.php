<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Exception\EncodeException;
use Argo\Serializer\Exception\UnsupportedFormatException;

interface EncoderInterface
{
    /**
     * @throws EncodeException
     * @throws UnsupportedFormatException
     */
    public function encode(mixed $data, string $format, ContextBag $contextBag = new ContextBag()): string;

    public function supportsEncoding(string $format, ContextBag $contextBag = new ContextBag()): bool;
}
