<?php

declare(strict_types=1);

namespace Argo\Serializer\Discriminator;

use Argo\AttributeCollector\Contract\AttributeManagerInterface;
use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\Serializer\Attribute\Discriminator;
use Argo\Serializer\Attribute\DiscriminatorMap;
use Argo\Serializer\Contract\DiscriminatorEnricherInterface;

final readonly class DiscriminatorEnricher implements DiscriminatorEnricherInterface
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private AttributeManagerInterface $attributeManager,
    ) {}

    public function enrich(object $object, array $normalizedObject, AttributeCollection $attributeCollection = new AttributeCollection()): array
    {
        $result = $this->enrichByCustom($object, $normalizedObject, $attributeCollection);
        if ($result !== null) {
            return $result;
        }

        $result = $this->enrichByDiscriminatorMap($object, $normalizedObject, $attributeCollection);
        if ($result !== null) {
            return $result;
        }

        return $normalizedObject;
    }

    private function enrichByCustom(object $object, array $normalizedObject, AttributeCollection $attributeCollection): ?array
    {
        $attribute = $this->findAttribute(Discriminator::class, $attributeCollection, $object::class);

        return $attribute?->discriminatorEnricher?->enrich($object, $normalizedObject, $attributeCollection);
    }

    private function enrichByDiscriminatorMap(object $object, array $normalizedObject, AttributeCollection $attributeCollection): ?array
    {
        $attribute = $this->findAttribute(DiscriminatorMap::class, $attributeCollection, $object::class);
        if ($attribute === null) {
            return null;
        }

        foreach ($attribute->map as $key => $className) {
            if ($className === $object::class) {
                $normalizedObject[$attribute->fieldName] = $key;
                break;
            }
        }

        return $normalizedObject;
    }

    /**
     * @template TAttribute
     * @param class-string<TAttribute> $attributeName
     * @param class-string $className
     * @psalm-return TAttribute|null
     */
    private function findAttribute(string $attributeName, AttributeCollection $attributeCollection, string $className): ?object
    {
        $attribute = $attributeCollection->firstByType($attributeName);
        if ($attribute !== null) {
            return $attribute;
        }

        return $this->findAttributeInClass($attributeName, $className);
    }

    /**
     * @template TAttribute
     * @param class-string<TAttribute> $attributeName
     * @param class-string $className
     * @psalm-return TAttribute|null
     */
    private function findAttributeInClass(string $attributeName, string $className): ?object
    {
        $attribute = $this->attributeManager->getAttributesForClass($className)->firstByType($attributeName);
        if ($attribute !== null) {
            return $attribute;
        }

        $implementedInterfaces = class_implements($className);
        if ($implementedInterfaces !== false) {
            foreach ($implementedInterfaces as $implementedInterface) {
                $attribute = $this->attributeManager->getAttributesForClass($implementedInterface)->firstByType($attributeName);
                if ($attribute !== null) {
                    return $attribute;
                }
            }
        }

        $parentClass = get_parent_class($className);
        if ($parentClass !== false) {
            $attribute = $this->findAttributeInClass($attributeName, $parentClass);
            if ($attribute !== null) {
                return $attribute;
            }
        }

        return null;
    }
}
