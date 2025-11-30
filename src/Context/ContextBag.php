<?php

declare(strict_types=1);

namespace Argo\Serializer\Context;

use Argo\Serializer\Contract\ContextInterface;

/**
 * @api
 */
class ContextBag
{
    /**
     * @var array<class-string<ContextInterface>, ContextInterface>
     */
    private array $context = [];

    public function __construct(ContextInterface ...$context)
    {
        foreach ($context as $item) {
            $this->context[$item::class] = $item;
        }
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @template TContext of ContextInterface
     * @param class-string<TContext> $type
     * @psalm-return TContext
     */
    public function get(string $type): ContextInterface
    {
        if (!array_key_exists($type, $this->context)) {
            $this->context[$type] = new $type();
        }

        /** @var TContext */
        return $this->context[$type];
    }

    /**
     * @return array<class-string<ContextInterface>, ContextInterface>
     */
    public function all(): array
    {
        return $this->context;
    }

    public function merge(ContextBag $context): ContextBag
    {
        $resultContext = array_merge($this->context, $context->all());

        return new self(...$resultContext);
    }

    public function with(ContextInterface ...$context): ContextBag
    {
        $contextArray = [];
        foreach ($context as $item) {
            $contextArray[$item::class] = $item;
        }

        $resultContext = array_merge($this->context, $contextArray);

        return new self(...$resultContext);
    }
}
