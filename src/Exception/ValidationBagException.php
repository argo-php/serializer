<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception;

/**
 * @api
 */
class ValidationBagException extends SerializerException
{
    /** @var array<ValidationException> */
    public array $exceptions;

    public function __construct(ValidationException ...$exceptions)
    {
        $this->exceptions = $exceptions;
        parent::__construct('Validation exception');
    }

    public function addException(ValidationException|ValidationBagException $exception): void
    {
        if ($exception instanceof self) {
            $this->exceptions = array_merge($this->exceptions, $exception->exceptions);
        } else {
            $this->exceptions[] = $exception;
        }
    }

    public function empty(): bool
    {
        return count($this->exceptions) === 0;
    }
}
