<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception;

use Argo\Types\TypeInterface;

/**
 * @api
 */
class InvalidDataTypeException extends InvalidArgumentException implements HasContextInterface
{
    public string $actualType;

    public function __construct(
        public mixed $data,
        public TypeInterface $expectedTypes,
    ) {
        $this->actualType = get_debug_type($data);

        $message = sprintf(
            'The data must have type of [%s], actual is [%s]',
            $expectedTypes,
            $this->actualType,
        );
        parent::__construct($message);
    }

    public function context(): array
    {
        return [
            'actualType' => $this->actualType,
            'expectedTypes' => $this->expectedTypes,
        ];
    }
}
