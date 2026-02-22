<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception;

use Argo\Serializer\Context\Internal\PathContext;

/**
 * @api
 */
class ValidationException extends SerializerException implements HasContextInterface
{
    public function __construct(
        private readonly PathContext|string $field,
        private readonly string $rule,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function getField(): string
    {
        return (string) $this->field;
    }

    public function getRule(): string
    {
        return $this->rule;
    }

    public function context(): array
    {
        return [
            'message' => $this->getMessage(),
            'field' => $this->getField(),
            'rule' => $this->getRule(),
        ];
    }
}
