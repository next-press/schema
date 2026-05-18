<?php

declare(strict_types=1);

namespace Auroro\Schema;

/**
 * Maps JSON Schema (from SchemaFactory) to WordPress DataViews field definitions.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/
 */
final class DataViewsFieldMapper
{
    /**
     * Convert a JSON Schema (from SchemaFactory::buildProperties) to DataViews fields.
     *
     * @param array{type: string, properties: array<string, array<string, mixed>>, required: list<string>, additionalProperties: bool} $schema
     * @return list<array<string, mixed>>
     */
    public function map(array $schema): array
    {
        $fields = [];
        $required = $schema['required'] ?? [];

        foreach ($schema['properties'] ?? [] as $name => $prop) {
            $field = [
                'id' => $name,
                'label' => $this->humanize($name),
                'type' => $this->mapType($prop),
                'enableGlobalSearch' => $this->isSearchable($prop),
            ];

            // Enum → elements dropdown
            if (isset($prop['enum'])) {
                $field['elements'] = array_map(
                    static fn (string|int $value): array => [
                        'value' => $value,
                        'label' => ucfirst(str_replace('_', ' ', (string) $value)),
                    ],
                    $prop['enum'],
                );
            }

            // Validation rules
            $validation = [];
            if (in_array($name, $required, true)) {
                $validation['required'] = true;
            }
            if (isset($prop['minLength'])) {
                $validation['minLength'] = $prop['minLength'];
            }
            if (isset($prop['maxLength'])) {
                $validation['maxLength'] = $prop['maxLength'];
            }
            if (isset($prop['minimum'])) {
                $validation['min'] = $prop['minimum'];
            }
            if (isset($prop['maximum'])) {
                $validation['max'] = $prop['maximum'];
            }
            if (isset($prop['pattern'])) {
                $validation['pattern'] = $prop['pattern'];
            }
            if ($validation !== []) {
                $field['isValid'] = $validation;
            }

            // Description from schema
            if (isset($prop['description']) && $prop['description'] !== '') {
                $field['description'] = $prop['description'];
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Map JSON Schema type to DataViews field type.
     *
     * @param array<string, mixed> $prop
     */
    private function mapType(array $prop): string
    {
        // Enum fields with string values → text with elements
        if (isset($prop['enum'])) {
            return 'text';
        }

        // Handle format hints
        if (isset($prop['format'])) {
            return match ($prop['format']) {
                'date-time' => 'datetime',
                'date' => 'date',
                'email' => 'email',
                'uri' => 'url',
                default => 'text',
            };
        }

        $type = $prop['type'] ?? 'text';

        // Nullable types: ["string", "null"] → use the non-null type
        if (is_array($type)) {
            $type = array_values(array_filter($type, static fn (string $t): bool => $t !== 'null'))[0] ?? 'text';
        }

        return match ($type) {
            'string' => 'text',
            'integer' => 'integer',
            'number' => 'number',
            'boolean' => 'boolean',
            'array' => 'array',
            default => 'text',
        };
    }

    /**
     * Determine if a field should be included in global search.
     *
     * @param array<string, mixed> $prop
     */
    private function isSearchable(array $prop): bool
    {
        $type = $prop['type'] ?? '';
        if (is_array($type)) {
            $type = array_values(array_filter($type, static fn (string $t): bool => $t !== 'null'))[0] ?? '';
        }

        return $type === 'string' && ! isset($prop['enum']) && ! isset($prop['format']);
    }

    /**
     * Convert snake_case or camelCase to a human-readable label.
     */
    private function humanize(string $name): string
    {
        // camelCase → space separated
        $spaced = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name) ?? $name;
        // snake_case → space separated
        $spaced = str_replace('_', ' ', $spaced);

        return ucfirst($spaced);
    }
}
