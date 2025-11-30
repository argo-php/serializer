<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception;

use Argo\Types\TypeInterface;

final class InvalidArgumentTypeException extends InvalidArgumentException
{
    public function __construct(TypeInterface $actualType, TypeInterface $expectedTypes)
    {
        $message = sprintf(
            'The property must have type of [%s], actual is [%s]',
            $expectedTypes,
            $actualType,
        );
        parent::__construct($message);
    }
}
