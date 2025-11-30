<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

interface DenormalizerAwareInterface
{
    public function setDenormalizer(DenormalizerInterface $denormalizer): void;
}
