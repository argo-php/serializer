<?php

declare(strict_types=1);

namespace Argo\Serializer\Attribute;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
class DiscriminatorMap
{
    /**
     * @param array<int|string, class-string> $map
     * @param class-string|null $defaultClassName
     */
    public function __construct(
        public string $fieldName,
        public array $map,
        public ?string $defaultClassName = null,
    ) {}
}
