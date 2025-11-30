<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\Serializer\Context\ContextBag;

interface DecoderInterface
{
    public function decode(string $data, string $format, ContextBag $contextBag = new ContextBag()): mixed;

    public function supportsDecoding(string $format, ContextBag $contextBag = new ContextBag()): bool;
}
