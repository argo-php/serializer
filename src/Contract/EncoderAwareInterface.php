<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

interface EncoderAwareInterface
{
    public function setEncoder(EncoderInterface $encoder): void;
}
