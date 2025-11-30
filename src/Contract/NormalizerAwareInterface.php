<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

interface NormalizerAwareInterface
{
    public function setNormalizer(NormalizerInterface $normalizer): void;
}
