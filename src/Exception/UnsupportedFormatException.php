<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception;

/**
 * @api
 */
class UnsupportedFormatException extends SerializerException
{
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, 1100, $previous);
    }
}
