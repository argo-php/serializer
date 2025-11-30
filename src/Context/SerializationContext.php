<?php

declare(strict_types=1);

namespace Argo\Serializer\Context;

use Argo\Serializer\Contract\ContextInterface;

/**
 * @api
 */
readonly class SerializationContext implements ContextInterface
{
    public function __construct(
        public bool $validateBeforeDenormalization = true,
        public bool $stopOnFirstValidationError = false,
        public bool $normalizeAsArray = false,
        public bool|int $serializationDepth = false,
        public int $circularReferenceLimit = 1,
        public bool $throwOnCircularReference = true,
        public ?string $group = null,
    ) {}
}
