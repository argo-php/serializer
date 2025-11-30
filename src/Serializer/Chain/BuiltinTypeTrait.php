<?php

declare(strict_types=1);

namespace Argo\Serializer\Serializer\Chain;

trait BuiltinTypeTrait
{
    protected function isBuiltinType(mixed $data): bool
    {
        return match (get_debug_type($data)) {
            'int', 'float', 'string', 'bool', 'null' => true,
            default => false,
        };
    }
}
