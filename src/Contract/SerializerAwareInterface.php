<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

interface SerializerAwareInterface
{
    public function setSerializer(SerializerInterface $serializer): void;
}
