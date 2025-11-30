<?php

declare(strict_types=1);

namespace Argo\Serializer\Aware;

use Argo\Serializer\Contract\EncoderInterface;
use Argo\Serializer\Exception\LogicException;

/**
 * @api
 */
trait EncoderAwareTrait
{
    private ?EncoderInterface $encoder = null;

    public function setEncoder(EncoderInterface $encoder): void
    {
        $this->encoder = $encoder;
    }

    public function getEncoder(): EncoderInterface
    {
        return $this->encoder ?? throw new LogicException('Encoder not set');
    }
}
