<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

interface DecoderAwareInterface
{
    public function setDecoder(DecoderInterface $decoder): void;
}
