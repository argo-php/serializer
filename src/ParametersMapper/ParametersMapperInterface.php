<?php

declare(strict_types=1);

namespace Argo\Serializer\ParametersMapper;

/**
 * @api
 */
interface ParametersMapperInterface
{
    public function parseMethodParameters(\ReflectionMethod $reflectionMethod, array $arguments): array;
}
