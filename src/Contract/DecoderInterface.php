<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Exception\DecodeException;
use Argo\Serializer\Exception\UnsupportedFormatException;

interface DecoderInterface
{
    /**
     * @throws DecodeException
     * @throws UnsupportedFormatException
     */
    public function decode(string $data, string $format, ContextBag $contextBag = new ContextBag()): mixed;

    public function supportsDecoding(string $format, ContextBag $contextBag = new ContextBag()): bool;
}
