<?php

namespace Argo\Serializer\DI;

use Argo\Serializer\ContextFiller\AttributeContextFiller;
use Argo\Serializer\ContextFiller\AttributeContextFillerInterface;
use Argo\Serializer\ContextFiller\VariableContextFiller;
use Argo\Serializer\ContextFiller\VariableContextFillerInterface;
use Argo\Serializer\Contract\DiscriminatorEnricherInterface;
use Argo\Serializer\Contract\DiscriminatorResolverInterface;
use Argo\Serializer\Discriminator\DiscriminatorEnricher;
use Argo\Serializer\Discriminator\DiscriminatorResolver;
use Argo\Serializer\ParametersMapper\ParametersMapper;
use Argo\Serializer\ParametersMapper\ParametersMapperInterface;

/**
 * @api
 */
final readonly class HyperfConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                DiscriminatorResolverInterface::class => DiscriminatorResolver::class,
                DiscriminatorEnricherInterface::class => DiscriminatorEnricher::class,
                ParametersMapperInterface::class => ParametersMapper::class,
                VariableContextFillerInterface::class => VariableContextFiller::class,
                AttributeContextFillerInterface::class => AttributeContextFiller::class,
            ],
        ];
    }
}
