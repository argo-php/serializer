<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception;

/**
 * @api
 */
class DecodeException extends SerializerException
{
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, 1110, $previous);
    }
}
