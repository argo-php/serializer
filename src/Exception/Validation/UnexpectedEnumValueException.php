<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception\Validation;

use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Exception\ValidationException;

/**
 * @api
 */
class UnexpectedEnumValueException extends ValidationException
{
    /**
     * @param list<int|string>|class-string<\BackedEnum> $expectedValues
     */
    public function __construct(PathContext|string $field, array|string $expectedValues)
    {
        if (is_a($expectedValues, \BackedEnum::class, true)) {
            $values = array_map(fn(\BackedEnum $enum) => (string) $enum->value, $expectedValues::cases());
        } elseif (is_array($expectedValues)) {
            $values = $expectedValues;
        } else {
            $values = [$expectedValues];
        }

        $message = sprintf('Incorrect value. Allows one of: %s', implode(', ', $values));
        parent::__construct($field, 'unexpected_value', $message);
    }
}
