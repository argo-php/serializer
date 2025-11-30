<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception;

use Argo\Serializer\Context\Internal\PathContext;

/**
 * @api
 */
class CircularReferenceException extends SerializerException
{
    public function __construct(
        private readonly PathContext $pathContext,
    ) {
        parent::__construct('Circular reference when serialize object');
    }

    public function getPathContext(): PathContext
    {
        return $this->pathContext;
    }
}
