<?php

declare(strict_types=1);

namespace Argo\Serializer\Aware;

use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Exception\LogicException;

/**
 * @api
 */
trait DenormalizerAwareTrait
{
    private ?DenormalizerInterface $denormalizer = null;

    public function setDenormalizer(DenormalizerInterface $denormalizer): void
    {
        $this->denormalizer = $denormalizer;
    }

    public function getDenormalizer(): DenormalizerInterface
    {
        return $this->denormalizer ?? throw new LogicException('Denormalizer not set');
    }
}
