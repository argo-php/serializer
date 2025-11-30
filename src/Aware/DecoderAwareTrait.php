<?php

declare(strict_types=1);

namespace Argo\Serializer\Aware;

use Argo\Serializer\Contract\DecoderInterface;
use Argo\Serializer\Exception\LogicException;

/**
 * @api
 */
trait DecoderAwareTrait
{
    private ?DecoderInterface $decoder = null;

    public function setDecoder(DecoderInterface $decoder): void
    {
        $this->decoder = $decoder;
    }

    public function getDecoder(): DecoderInterface
    {
        return $this->decoder ?? throw new LogicException('Decoder not set');
    }
}
