<?php

declare(strict_types=1);

namespace Argo\Serializer\Aware;

use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Exception\LogicException;

/**
 * @api
 */
trait NormalizerAwareTrait
{
    private ?NormalizerInterface $normalizer = null;

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizer = $normalizer;
    }

    public function getNormalizer(): NormalizerInterface
    {
        return $this->normalizer ?? throw new LogicException('Normalizer not set');
    }
}
