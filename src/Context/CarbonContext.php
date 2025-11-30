<?php

declare(strict_types=1);

namespace Argo\Serializer\Context;

use Argo\Serializer\Contract\ContextInterface;

/**
 * @api
 */
readonly class CarbonContext implements ContextInterface
{
    public function __construct(
        public string $format = \DateTimeInterface::RFC3339,
        public ?string $timezone = null,
        public string $intervalFormat = '%rP%yY%mM%dDT%hH%iM%sS',
        public ?string $intervalLocale = null,
    ) {}

    public function setFormat(string $format): self
    {
        return new self($format, $this->timezone, $this->intervalFormat, $this->intervalLocale);
    }

    public function setTimezone(?string $timezone): self
    {
        return new self($this->format, $timezone, $this->intervalFormat, $this->intervalLocale);
    }

    public function setIntervalFormat(string $intervalFormat): self
    {
        return new self($this->format, $this->timezone, $intervalFormat, $this->intervalLocale);
    }

    public function setIntervalLocale(?string $intervalLocale): self
    {
        return new self($this->format, $this->timezone, $this->intervalFormat, $intervalLocale);
    }
}
