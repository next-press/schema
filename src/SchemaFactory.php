<?php

declare(strict_types=1);

namespace Auroro\Schema;

use Auroro\Schema\Attribute\With;

/**
 * @phpstan-type JsonSchema array{
 *     type: 'object',
 *     properties: array<string, array<string, mixed>>,
 *     required: list<string>,
 *     additionalProperties: false,
 * }
 */
final class SchemaFactory
{
    public function __construct(
        private readonly DescriptionParser $descriptionParser = new DescriptionParser(),
    ) {}

    /**
     * @return JsonSchema|null
     */
    public function buildParameters(string $className, string $methodName): ?array
    {
        $reflection = new \ReflectionMethod($className, $methodName);

        return $this->convertTypes($reflection->getParameters());
    }

    /**
     * @param class-string $className
     * @return JsonSchema|null
     */
    public function buildProperties(string $className): ?array
    {
        $reflection = new \ReflectionClass($className);

        return $this->convertTypes($reflection->getProperties());
    }

    /**
     * @param list<\ReflectionProperty|\ReflectionParameter> $elements
     *
     * @return JsonSchema|null
     */
    private function convertTypes(array $elements): ?array
    {
        if ([] === $elements) {
            return null;
        }

        $result = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
            'additionalProperties' => false,
        ];

        foreach ($elements as $element) {
            $name = $element->getName();
            $type = $element instanceof \ReflectionParameter
                ? ReflectedType::fromParameter($element)
                : ReflectedType::fromProperty($element);

            $schema = $this->getTypeSchema($type);

            if ($type->isNullable) {
                if (!isset($schema['anyOf'])) {
                    $schema['type'] = [$schema['type'], 'null'];
                }
            }

            if (!($element instanceof \ReflectionParameter && $element->isOptional())) {
                $result['required'][] = $name;
            }

            $description = $this->descriptionParser->getDescription($element);
            if ('' !== $description) {
                $schema['description'] = $description;
            }

            $attributes = $element->getAttributes(With::class);
            if (\count($attributes) > 0) {
                $attributeState = array_filter((array) $attributes[0]->newInstance(), static fn(mixed $value): bool => null !== $value);
                $schema = array_merge($schema, $attributeState);
            }

            $result['properties'][$name] = $schema;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function getTypeSchema(ReflectedType $type): array
    {
        $unwrapped = $type->unwrapNullable();

        if ($unwrapped->isBackedEnum()) {
            /** @var class-string<\BackedEnum> $enumClass */
            $enumClass = $unwrapped->name;

            return $this->buildEnumSchema($enumClass);
        }

        // Union types (non-nullable)
        if (\count($unwrapped->unionTypes) > 1) {
            $variants = [];
            foreach ($unwrapped->unionTypes as $variant) {
                if ('null' !== $variant->name) {
                    $variants[] = $this->getTypeSchema($variant);
                }
            }

            return ['anyOf' => $variants];
        }

        return match ($unwrapped->name) {
            'int' => ['type' => 'integer'],
            'float' => ['type' => 'number'],
            'bool' => ['type' => 'boolean'],
            'null' => ['type' => 'null'],
            'array' => $this->buildArraySchema($unwrapped),
            'string' => ['type' => 'string'],
            default => $this->buildObjectSchema($unwrapped),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildArraySchema(ReflectedType $type): array
    {
        $itemType = $type->collectionValueType;

        if (null === $itemType) {
            return ['type' => 'array', 'items' => ['type' => 'string']];
        }

        if ($itemType->isObject()) {
            /** @var class-string $itemClass */
            $itemClass = $itemType->name;

            return [
                'type' => 'array',
                'items' => $this->buildProperties($itemClass),
            ];
        }

        return [
            'type' => 'array',
            'items' => $this->getTypeSchema($itemType),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildObjectSchema(ReflectedType $type): array
    {
        if ($type->isBuiltin) {
            throw new \InvalidArgumentException('Cannot build schema from plain object type.');
        }

        if (\in_array($type->name, ['DateTime', 'DateTimeImmutable', 'DateTimeInterface'], true)) {
            return ['type' => 'string', 'format' => 'date-time'];
        }

        /** @var class-string $className */
        $className = $type->name;

        return $this->buildProperties($className) ?? ['type' => 'object'];
    }

    /**
     * @param class-string<\BackedEnum> $enumClassName
     * @return array<string, mixed>
     */
    private function buildEnumSchema(string $enumClassName): array
    {
        $reflection = new \ReflectionEnum($enumClassName);

        $values = [];
        /** @var \ReflectionNamedType $backingType */
        $backingType = $reflection->getBackingType();

        foreach ($reflection->getCases() as $case) {
            /** @var \ReflectionEnumBackedCase $case */
            $values[] = $case->getBackingValue();
        }

        $typeName = $backingType->getName();
        $jsonType = 'int' === $typeName ? 'integer' : 'string';

        return [
            'type' => $jsonType,
            'enum' => $values,
        ];
    }
}
