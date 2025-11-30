<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception\Validation;

use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Exception\ValidationException;

/**
 * @api
 */
class UnexpectedValueFormatException extends ValidationException
{
    public function __construct(PathContext|string $field, string $expectedFormat)
    {
        $message = sprintf('Incorrect value format. Expected: %s', $expectedFormat);
        parent::__construct($field, 'unexpected_format', $message);
    }
}
