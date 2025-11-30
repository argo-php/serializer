<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception\Validation;

use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Exception\ValidationException;
use Argo\Types\TypeInterface;

final class IncorrectTypeException extends ValidationException
{
    public function __construct(PathContext|string $field, TypeInterface|string $expectedType, TypeInterface|string $actualType)
    {
        $message = sprintf(
            'Incorrect type. Expected: [%s], actual: [%s]',
            $expectedType,
            $actualType,
        );

        parent::__construct($field, 'incorrect_type', $message);
    }
}
