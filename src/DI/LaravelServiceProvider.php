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
use Illuminate\Support\ServiceProvider;

/**
 * @api
 */
final class LaravelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DiscriminatorResolverInterface::class, DiscriminatorResolver::class);
        $this->app->bind(DiscriminatorEnricherInterface::class, DiscriminatorEnricher::class);
        $this->app->bind(ParametersMapperInterface::class, ParametersMapper::class);
        $this->app->bind(VariableContextFillerInterface::class, VariableContextFiller::class);
        $this->app->bind(AttributeContextFillerInterface::class, AttributeContextFiller::class);
    }
}
