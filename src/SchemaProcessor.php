<?php

declare(strict_types=1);

namespace Auroro\Schema;

use Auroro\Result\Err;
use Auroro\Result\Ok;
use Auroro\Result\Result;
use Auroro\Schema\Attribute\With;

final class SchemaProcessor
{
    public function __construct(
        private readonly Hydrator $hydrator = new Hydrator(),
    ) {}

    /**
     * Validate data against a class schema and hydrate if valid.
     *
     * @template T of object
     * @param array<string, mixed> $data
     * @param class-string<T> $class
     * @return Result<T, list<ValidationError>>
     */
    public function process(array $data, string $class): Result
    {
        $errors = $this->validate($data, $class);

        if ($errors !== []) {
            /** @var list<ValidationError> $errors */
            return new Err($errors);
        }

        return new Ok($this->hydrator->hydrate($data, $class));
    }

    /**
     * @param array<string, mixed> $data
     * @param class-string $class
     * @return list<ValidationError>
     */
    private function validate(array $data, string $class, string $prefix = ''): array
    {
        $errors = [];
        $ref = new \ReflectionClass($class);
        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return $errors;
        }

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $path = $prefix === '' ? $name : "{$prefix}.{$name}";
            $hasValue = \array_key_exists($name, $data);
            $value = $data[$name] ?? null;

            // Required check
            if (!$hasValue && !$param->isOptional()) {
                $type = $param->getType();
                $nullable = $type instanceof \ReflectionNamedType && $type->allowsNull();

                if (!$nullable) {
                    $errors[] = new ValidationError($path, \sprintf("Required field '%s' is missing", $name), 'required');

                    continue;
                }
            }

            if (!$hasValue || $value === null) {
                continue;
            }

            // Type check
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
                $errors = [...$errors, ...$this->validateType($path, $type->getName(), $value)];
            }

            // With attribute constraints
            $attributes = $param->getAttributes(With::class);

            if ($attributes !== []) {
                $with = $attributes[0]->newInstance();
                $errors = [...$errors, ...$this->validateConstraints($path, $with, $value)];
            }
        }

        return $errors;
    }

    /**
     * @return list<ValidationError>
     */
    private function validateType(string $path, string $expectedType, mixed $value): array
    {
        $valid = match ($expectedType) {
            'int' => \is_int($value),
            'float' => is_numeric($value),
            'bool' => \is_bool($value),
            'string' => \is_string($value),
            'array' => \is_array($value),
            default => true,
        };

        if (!$valid) {
            return [new ValidationError($path, \sprintf("Expected type '%s', got '%s'", $expectedType, get_debug_type($value)), 'type')];
        }

        return [];
    }

    /**
     * @return list<ValidationError>
     */
    private function validateConstraints(string $path, With $with, mixed $value): array
    {
        $errors = [];

        // String constraints
        if (\is_string($value)) {
            if ($with->minLength !== null && mb_strlen($value) < $with->minLength) {
                $errors[] = new ValidationError($path, \sprintf('Must be at least %d characters', $with->minLength), 'minLength');
            }

            if ($with->maxLength !== null && mb_strlen($value) > $with->maxLength) {
                $errors[] = new ValidationError($path, \sprintf('Must be at most %d characters', $with->maxLength), 'maxLength');
            }

            if ($with->pattern !== null && !preg_match('/' . $with->pattern . '/', $value)) {
                $errors[] = new ValidationError($path, \sprintf('Does not match pattern "%s"', $with->pattern), 'pattern');
            }

            if ($with->format !== null) {
                $errors = [...$errors, ...$this->validateFormat($path, $with->format, $value)];
            }
        }

        // Numeric constraints
        if (is_numeric($value)) {
            $num = \is_int($value) ? $value : (float) $value;

            if ($with->minimum !== null && $num < $with->minimum) {
                $errors[] = new ValidationError($path, \sprintf('Must be at least %d', $with->minimum), 'minimum');
            }

            if ($with->maximum !== null && $num > $with->maximum) {
                $errors[] = new ValidationError($path, \sprintf('Must be at most %d', $with->maximum), 'maximum');
            }

            if ($with->exclusiveMinimum !== null && $num <= $with->exclusiveMinimum) {
                $errors[] = new ValidationError($path, \sprintf('Must be greater than %d', $with->exclusiveMinimum), 'exclusiveMinimum');
            }

            if ($with->exclusiveMaximum !== null && $num >= $with->exclusiveMaximum) {
                $errors[] = new ValidationError($path, \sprintf('Must be less than %d', $with->exclusiveMaximum), 'exclusiveMaximum');
            }

            if ($with->multipleOf !== null && $num % $with->multipleOf !== 0) {
                $errors[] = new ValidationError($path, \sprintf('Must be a multiple of %d', $with->multipleOf), 'multipleOf');
            }
        }

        // Array constraints
        if (\is_array($value)) {
            if ($with->minItems !== null && \count($value) < $with->minItems) {
                $errors[] = new ValidationError($path, \sprintf('Must have at least %d items', $with->minItems), 'minItems');
            }

            if ($with->maxItems !== null && \count($value) > $with->maxItems) {
                $errors[] = new ValidationError($path, \sprintf('Must have at most %d items', $with->maxItems), 'maxItems');
            }

            if ($with->uniqueItems === true && \count($value) !== \count(array_unique($value, SORT_REGULAR))) {
                $errors[] = new ValidationError($path, 'Items must be unique', 'uniqueItems');
            }
        }

        // Enum constraint
        if ($with->enum !== null && !\in_array($value, $with->enum, false)) {
            $errors[] = new ValidationError($path, \sprintf('Must be one of: %s', implode(', ', array_map('strval', $with->enum))), 'enum');
        }

        return $errors;
    }

    /**
     * @return list<ValidationError>
     */
    private function validateFormat(string $path, string $format, string $value): array
    {
        $valid = match ($format) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'uri' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'uuid' => (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value),
            'date-time' => \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC3339, $value) !== false
                || \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.uP', $value) !== false,
            'date' => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value),
            'time' => (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value),
            'ipv4' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
            'ipv6' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false,
            'hostname' => filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false,
            default => true, // @codeCoverageIgnore
        };

        if (!$valid) {
            return [new ValidationError($path, \sprintf('Invalid %s format', $format), 'format')];
        }

        return [];
    }
}
