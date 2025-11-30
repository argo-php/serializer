# argo-php/serializer

An object serializer (hydrator) for PHP.

It is necessary for serializing complex structured objects into string representations (JSON, XML, etc.) and for
deserializing these objects from their string representations.

Its architecture is similar to the symfony/serializer package (some implementations are copied from there unchanged), 
so if you have any questions not answered in this documentation, you can refer to the original
documentation at https://symfony.com/doc/current/serializer.html

Key differences from `symfony/serializer`:
* context is passed as context objects collected in ContextBag (as opposed to arrays in the original package)
* extended set of attributes for setting context
* built-in data validation during denormalization
* collection of class structure information is handled via the `argo/entity-definition` package
* the package works entirely based on types from the `argo/types` package (as opposed to string-based type passing in the original package)

## Basic concepts

### Normalizer / Denormalizer

A normalizer is a special class that can transform data of various types (including objects) into a unified representation (array or stdClass), which can then be easily represented as a string.

A denormalizer performs the reverse task of mapping an array/stdClass to specified types.

There are several ready-made normalizers:
* **ArrayableNormalizer** - Performs normalization of objects implementing the `Hyperf\Contract\Arrayable` and
  `Illuminate\Contracts\Support\Arrayable` interfaces.
* **ArrayNormalizer** - performs normalization and denormalization of iterable types (arrays and Traversable objects).
* **BackedEnumNormalizer** - Normalizes and denormalizes enumerations (enams)
* **CarbonNormalizer** - Normalizes and denormalizes objects of the `Carbon\Carbon`, `Carbon\CarbonImmutable`, and
  `Carbon\CarbonInterval` types
* **CustomNormalizer** - Normalizes objects implementing the
  `Argo\Serializer\Contract\NormalizableInterface` interface and denormalizes objects implementing the
  `Argo\Serializer\Contract\DenormalizableInterface` interface - that is, objects that control their own
  normalization and denormalization process
* **JsonSerializableNormalizer** - Normalizes objects implementing the `JsonSerializable` interface
* **ObjectNormalizer** - Normalizes and denormalizes any objects. Normalizes only public fields of objects
  and can denormalize using the object constructor. Does not use getters and setters to obtain field values.
* **UnionDenormalizer** - denormalizes a value into a Union type using a DiscriminatorResolver.

### Encoder / Decoder

An encoder converts a normalized object/array to a given format (JSON/XML/etc.).
A decoder performs the opposite function: it converts a string in one of the formats into a normalized object/array.

This package contains only unified interfaces for working with encoders/decoders. The implementations for the required formats are provided in separate packages:

* **argo/json-encoder** - contains an encoder and decoder for working with JSON
* **argo/xml-encoder** - contains an encoder and decoder for working with XML

### Context and ContextBag

A context is a special object that contains additional information for normalizers and encoders. A context
allows you to control the normalization mechanism, such as changing field names, setting time and date formats, and
indicating that a field in an object should not be serialized.

A ContextBag is a collection of objects with contexts. It allows you to retrieve the current context by the class name of that context.

A context can be set in advance when running serialization/deserialization/normalization/denormalization/encoding/decoding methods.
A context can also be set using special attributes.

### Attributes for setting context

* **DateIntervalFormat** - Sets the normalization format for CarbonInterval objects
* **DateTimeFormat** - Sets the format and time zone for normalizing Carbon and CarbonImmutable objects
* **DenormalizationContext** - Allows you to specify context attributes that will only be applied during denormalization processes
* **EnumContext** - Allows you to set normalization and denormalization settings for enumerations (enams)
* **GroupContext** - Allows you to specify context attributes that will only be applied to the specified serialization group
  (see below for more information on serialization groups)
* **Groups** - Allows you to specify a serialization group for a class, property, or method
* **Ignore** - An entity marked with this attribute will not be serialized or deserialized
* **IgnoreIfEmpty** - An entity marked with this attribute will not be serialized or deserialized if its value satisfies the method conditions `empty()`
* **IgnoreIfNull** - An entity marked with this attribute will not be serialized or deserialized if its
  value is `null`
* **NormalizationContext** - Allows you to specify context attributes that will only be used during normalization
* **SerializedName** - Allows you to specify an alias for a field that will be used during normalization and denormalization
* **SerializedPath** - Allows you to specify a serialization path for a field that will be used for normalization and denormalization
* **Timezone** - Allows you to specify a timezone for normalization and denormalization of Carbon and CarbonImmutable objects
* **XmlArray** - Only used for XML serialization and deserialization - Allows you to mark a property as an array of nodes.
  This is necessary because XML does not allow for unambiguous interpretation of an array of nodes within another node if there is only one nested node.
* **XmlAttribute** - allows you to specify that this property is an XML attribute.
* **XmlValue** - allows you to specify that this property is the value of an XML node.

### DiscriminatorResolver and DiscriminatorEnricher

DiscriminatorResolver is a specialized class designed to resolve ambiguities during data denormalization. Possible ambiguities: a Union type, an abstract class, or an interface.
This class resolves ambiguities in several ways:
1. It checks whether the given field, or the abstract class that is the current field's type, has a
   `Discriminator` attribute, passed an instance of a custom DiscriminatorResolver implementing the
   `DiscriminatorResolverInterface` interface.
2. It checks for the presence of the `DiscriminatorMap` attribute, which specifies the rules for resolving such situations (the name of the field to look at and an array of values ​​with the corresponding types to apply).
3. It attempts to automatically select the most appropriate type from the Union type.

DiscriminatorEnricher is a class that performs the reverse operation: it augments normalized data with additional
data that helps correctly interpret the various types represented in a given field.

In simple terms, it adds an additional field with a value that helps us understand what type the field was before
normalization.

Its algorithm is similar to DiscriminatorResolver's: it first checks for the presence of the `Discriminator` attribute with a specified
custom DiscriminatorEnricher implementing the `DiscriminatorEnricherInterface` interface, and then checks for the presence of the
`DiscriminatorMap` attribute.

### Serializer

This is the connection point for all previous entities. This means that serialization/deserialization processes occur through an instance of this class. Direct interaction with normalizers and encoders is not required, but preliminary configuration of the serializer based on your needs is required. An initialization example is provided in the "Usage Examples" section.

Contains the following methods:

```php
public function serialize(mixed $data, string $format, ContextBag $contextBag = new ContextBag()): string
```
* `$data` - the data to be serialized
* `$format` - the output data format (`json` for JsonEncoder, `xml` for XmlEncoder)
* `$contextBag` - the initial serialization context

```php
public function deserialize(string $data, TypeInterface $type, string $format, ContextBag $contextBag = new ContextBag()): mixed
```
* `$data` - input serialized data
* `$type` - type to deserialize data into (see package `argo/types`)
* `$format` - format to deserialize data from (`json`, `xml`)
* `$contextBag` - initial deserialization context

There are also methods that allow you to perform not full serialization/deserialization, but only part of the stage:

```php
public function normalize(mixed $data, string $format = null, ContextBag $contextBag = new ContextBag()): array|string|int|float|bool|object|null
```
Normalizes transmitted data
```php
public function encode(mixed $data, string $format, ContextBag $contextBag = new ContextBag()): string
```
Encodes data into the required format from a normalized form
```php
public function decode(string $data, string $format, ContextBag $contextBag = new ContextBag()): mixed
```
Decodes serialized data into a normalized form (object/array)
```php
public function denormalize(mixed $data, TypeInterface $type, ?string $format = null, ContextBag $contextBag = new ContextBag()): mixed
```
Denormalizes data from its normalized form to the specified type.

### Normalization Groups

A special feature that allows you to specify in the context which group a particular class property (or the class itself) belongs to. Different context settings can then be applied to different groups, or certain fields can be ignored when serializing a specific group. The primary use case is to have different groups for serializing and deserializing data
to the API (JSON) and to the database (for example, omitting certain fields from the DTO to the API but storing them in the database, or having different date formats).

## Examples of use

Initializing the serializer:
```php
/** 
 * Here we specify all the normalizers we need.
 * The order of their specification matters, meaning more specific normalizers (such as CarbonNormalizer or BackedEnumNormalizer)
 * must be specified before more general normalizers (such as ObjectNormalizer).
 *
 * Normalizers are created through the container, as some require additional dependencies in the constructor.
 */
/** @var \Psr\Container\ContainerInterface $container */
$normalizers = [
    $container->get(\Argo\Serializer\Normalizer\UnionDenormalizer::class),
    $container->get(\Argo\Serializer\Normalizer\ArrayNormalizer::class),
    $container->get(\Argo\Serializer\Normalizer\CustomNormalizer::class),
    $container->get(\Argo\Serializer\Normalizer\ArrayableNormalizer::class),
    $container->get(\Argo\Serializer\Normalizer\JsonSerializableNormalizer::class),
    $container->get(\Argo\Serializer\Normalizer\CarbonNormalizer::class),
    $container->get(\Argo\Serializer\Normalizer\BackedEnumNormalizer::class),
    $container->get(\Argo\Serializer\Normalizer\ObjectNormalizer::class),
    $container->get(\Argo\Serializer\Normalizer\BuiltinDenormalizer::class),
];

/**
 * Here we specify all the encoders we need. Please note that to use them, you must install the corresponding packages.
 */
$encoders = [
    new \Argo\Serializer\JsonEncoder\JsonEncoder(), // available in the argo/json-encoder package
    new \Argo\Serializer\XmlEncoder\XmlEncoder(), // available in the argo/xml-encoder package
];

/**
 * This is a validator instance to support data validation before denormalization.
 * Can be omitted if validation is not needed.
 */
$validator = $container->make(\Argo\Serializer\Validator\SerializerValidatorInterface::class);

// это объект сериализатора. Через него будут производиться все дальнейшие действия по сериализации/десериализации
$serializer = new \Argo\Serializer\Serializer\Serializer($normalizers, $encoders, $validator);
```

Serialization and deserialization of an object
```php
class Person
{
    public function __construct(
        public int $age,
        public string $name,
        public bool $sportsperson
    ) {
    }
}

$person = new Person(39, 'Jane Doe', false);
/** @var \Argo\Serializer\Serializer\Serializer $serializer */
$jsonContent = $serializer->serialize($person, 'json');
// $jsonContent contains {"name":"Jane Doe","age":39,"sportsperson":false}

$object = $serializer->deserialize($jsonContent, new \Argo\Types\Atomic\ClassType(Person::class), 'json');
// $object - This is an object of the Person class with the age, name, and sportsperson fields filled in.
```
