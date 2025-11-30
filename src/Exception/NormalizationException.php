<?php

declare(strict_types=1);

namespace Argo\Serializer\Exception;

use Argo\Serializer\Context\Internal\PathContext;

/**
 * @api
 */
class NormalizationException extends SerializerException
{
    public function __construct(
        string $message,
        private readonly PathContext $pathContext,
        \Throwable $previous = null,
    ) {
        parent::__construct($message, 1300, $previous);
    }

    public function getPathContext(): PathContext
    {
        return $this->pathContext;
    }
}
