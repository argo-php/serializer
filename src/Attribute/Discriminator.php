<?php

declare(strict_types=1);

namespace Argo\Serializer\Attribute;

use Argo\Serializer\Contract\DiscriminatorEnricherInterface;
use Argo\Serializer\Contract\DiscriminatorResolverInterface;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
class Discriminator
{
    public function __construct(
        public ?DiscriminatorResolverInterface $discriminatorResolver = null,
        public ?DiscriminatorEnricherInterface $discriminatorEnricher = null,
    ) {}
}
