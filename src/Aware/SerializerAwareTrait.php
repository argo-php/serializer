<?php

declare(strict_types=1);

namespace Argo\Serializer\Aware;

use Argo\Serializer\Contract\SerializerInterface;
use Argo\Serializer\Exception\LogicException;

/**
 * @api
 */
trait SerializerAwareTrait
{
    private ?SerializerInterface $serializer = null;

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer ?? throw new LogicException('Serializer not set');
    }
}
