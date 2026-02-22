<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception;

use Argo\Types\TypeInterface;

/**
 * @api
 */
class InvalidArgumentTypeException extends InvalidArgumentException implements HasContextInterface
{
    public function __construct(
        public TypeInterface $actualType,
        public TypeInterface $expectedTypes,
    ) {
        $message = sprintf(
            'The property must have type of [%s], actual is [%s]',
            $expectedTypes,
            $actualType,
        );
        parent::__construct($message);
    }

    public function context(): array
    {
        return [
            'actualType' => (string) $this->actualType,
            'expectedTypes' => (string) $this->expectedTypes,
        ];
    }
}
