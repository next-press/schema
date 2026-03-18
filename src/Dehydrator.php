<?php

declare(strict_types=1);

namespace Auroro\Schema;

final class Dehydrator
{
    /**
     * Convert an object to a plain array suitable for serialization.
     *
     * @return array<string, mixed>
     */
    public function dehydrate(object $object): array
    {
        $data = [];
        $ref = new \ReflectionClass($object);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $data[$prop->getName()] = $this->dehydrateValue($prop->getValue($object));
        }

        return $data;
    }

    private function dehydrateValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        if (is_object($value)) {
            return $this->dehydrate($value);
        }

        if (is_array($value)) {
            return array_map($this->dehydrateValue(...), $value);
        }

        return $value;
    }
}
