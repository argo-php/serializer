<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception\Validation;

use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Exception\ValidationException;

/**
 * @api
 */
class RequiredException extends ValidationException
{
    public function __construct(PathContext|string $field)
    {
        parent::__construct($field, 'required', 'Expects required field');
    }
}
