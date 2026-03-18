<?php

declare(strict_types=1);

namespace Auroro\Schema;

final class Hydrator
{
    /**
     * @template T of object
     * @param array<string, mixed> $data
     * @param class-string<T> $class
     * @return T
     */
    public function hydrate(array $data, string $class): object
    {
        return $this->instantiate($class, $data);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $data
     * @return T
     */
    private function instantiate(string $class, array $data): object
    {
        $ref = new \ReflectionClass($class);
        $constructor = $ref->getConstructor();

        if (null === $constructor) {
            /** @var T */
            return $ref->newInstance();
        }

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (!\array_key_exists($name, $data)) {
                continue; // use PHP default
            }

            $args[$name] = $this->coerce($param, $data[$name]);
        }

        /** @var T */
        return new $class(...$args);
    }

    private function coerce(\ReflectionParameter $param, mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        $type = $param->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return $value;
        }

        if ($type->isBuiltin()) {
            return $this->coerceBuiltin($type->getName(), $value, $param);
        }

        return $this->coerceObject($type->getName(), $value);
    }

    private function coerceBuiltin(string $typeName, mixed $value, \ReflectionParameter $param): mixed
    {
        if ('array' === $typeName && \is_array($value)) {
            return $this->coerceArray($param, $value);
        }

        return match ($typeName) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => \is_string($value)
                ? \in_array(strtolower($value), ['true', '1'], true)
                : (bool) $value,
            'string' => (string) $value,
            'array' => (array) $value,
            default => $value,
        };
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private function coerceArray(\ReflectionParameter $param, array $value): array
    {
        $itemType = ReflectedType::fromParameter($param);

        if (null === $itemType->collectionValueType) {
            return $value;
        }

        $inner = $itemType->collectionValueType;

        if ($inner->isObject()) {
            /** @var class-string $innerClass */
            $innerClass = $inner->name;

            return array_map(
                fn(mixed $item): object => \is_array($item) ? $this->instantiate($innerClass, $item) : $item,
                $value,
            );
        }

        if ($inner->isBackedEnum()) {
            /** @var class-string<\BackedEnum> $enumClass */
            $enumClass = $inner->name;

            return array_map(
                static fn(mixed $item): \BackedEnum => $enumClass::from($item),
                $value,
            );
        }

        return $value;
    }

    private function coerceObject(string $typeName, mixed $value): mixed
    {
        if (enum_exists($typeName)) {
            if ($value instanceof \BackedEnum) {
                return $value;
            }
            /** @var class-string<\BackedEnum> $typeName */
            return $typeName::from($value);
        }

        if (is_a($typeName, \DateTimeInterface::class, true)) {
            if ($value instanceof \DateTimeInterface) {
                return $value;
            }
            /** @var class-string<\DateTimeInterface> $typeName */
            return new $typeName($value);
        }

        if (class_exists($typeName) && \is_array($value)) {
            /** @var class-string $typeName */
            return $this->instantiate($typeName, $value);
        }

        return $value;
    }
}
