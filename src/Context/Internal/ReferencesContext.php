<?php

declare(strict_types=1);

namespace Argo\Serializer\Context\Internal;

use Argo\Serializer\Contract\ContextInterface;

final readonly class ReferencesContext implements ContextInterface
{
    public function __construct(
        public array $references = [],
    ) {}

    public function getReferenceNumber(object $object): int
    {
        $objectId = $this->getObjectId($object);
        return array_key_exists($objectId, $this->references) ? $this->references[$objectId] : 0;
    }

    public function addReferenceCall(object $object): self
    {
        $references = $this->references;

        $objectId = $this->getObjectId($object);
        if (array_key_exists($objectId, $references)) {
            $references[$objectId] = $references[$objectId] + 1;
        } else {
            $references[$objectId] = 1;
        }

        return new self($references);
    }

    private function getObjectId(object $object): string
    {
        return spl_object_hash($object);
    }
}
