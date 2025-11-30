<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception;

use Argo\Types\TypeInterface;

/**
 * @api
 */
class InvalidDataTypeException extends InvalidArgumentException
{
    public function __construct(mixed $data, TypeInterface $expectedTypes)
    {
        $message = sprintf(
            'The data must have type of [%s], actual is [%s]',
            $expectedTypes,
            get_debug_type($data),
        );
        parent::__construct($message);
    }
}
