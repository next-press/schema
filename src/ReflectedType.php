<?php

declare(strict_types=1);

namespace Auroro\Schema;

final readonly class ReflectedType
{
    /**
     * @param list<self> $unionTypes
     */
    private function __construct(
        public string $name,
        public bool $isBuiltin,
        public bool $isNullable,
        public ?self $collectionValueType,
        public array $unionTypes,
    ) {}

    public function isBackedEnum(): bool
    {
        return !$this->isBuiltin && enum_exists($this->name) && (new \ReflectionEnum($this->name))->isBacked();
    }

    public function isObject(): bool
    {
        return !$this->isBuiltin && !$this->isBackedEnum();
    }

    public function unwrapNullable(): self
    {
        if (!$this->isNullable || [] === $this->unionTypes) {
            return $this;
        }

        $nonNull = array_values(array_filter($this->unionTypes, static fn(self $t): bool => 'null' !== $t->name));

        return 1 === \count($nonNull) ? $nonNull[0] : $this;
    }

    public static function fromParameter(\ReflectionParameter $param): self
    {
        $type = self::fromReflectionType($param->getType());

        if ('array' === $type->name && null === $type->collectionValueType) {
            $itemType = self::parseParamDocType($param);
            if (null !== $itemType) {
                return new self('array', true, $type->isNullable, $itemType, $type->unionTypes);
            }
        }

        return $type;
    }

    public static function fromProperty(\ReflectionProperty $prop): self
    {
        $type = self::fromReflectionType($prop->getType());

        if ('array' === $type->name && null === $type->collectionValueType) {
            $itemType = self::parseVarDocType($prop);

            // For promoted properties, fall back to constructor @param
            if (null === $itemType && $prop->isPromoted()) {
                $class = $prop->getDeclaringClass();
                $constructor = $class->getConstructor();
                if (null !== $constructor) {
                    foreach ($constructor->getParameters() as $param) {
                        if ($param->getName() === $prop->getName()) {
                            $itemType = self::parseParamDocType($param);
                            break;
                        }
                    }
                }
            }

            if (null !== $itemType) {
                return new self('array', true, $type->isNullable, $itemType, $type->unionTypes);
            }
        }

        return $type;
    }

    private static function fromReflectionType(?\ReflectionType $type): self
    {
        if (null === $type) {
            return new self('mixed', true, true, null, []);
        }

        if ($type instanceof \ReflectionNamedType) {
            return self::fromNamedType($type);
        }

        if ($type instanceof \ReflectionUnionType) {
            /** @var list<self> $members */
            $members = array_map(
                static fn(\ReflectionNamedType|\ReflectionIntersectionType $t): self => $t instanceof \ReflectionNamedType
                    ? self::fromNamedType($t)
                    : new self('mixed', true, false, null, []),
                $type->getTypes(),
            );

            $isNullable = $type->allowsNull();
            $nonNull = array_values(array_filter($members, static fn(self $t): bool => 'null' !== $t->name));

            if (1 === \count($nonNull) && $isNullable) {
                $inner = $nonNull[0];

                return new self($inner->name, $inner->isBuiltin, true, $inner->collectionValueType, $members);
            }

            $first = $nonNull[0] ?? $members[0];

            return new self($first->name, $first->isBuiltin, $isNullable, null, $members);
        }

        return new self('mixed', true, true, null, []);
    }

    private static function fromNamedType(\ReflectionNamedType $type): self
    {
        return new self(
            $type->getName(),
            $type->isBuiltin(),
            $type->allowsNull(),
            null,
            [],
        );
    }

    private static function parseParamDocType(\ReflectionParameter $param): ?self
    {
        $fn = $param->getDeclaringFunction();
        $doc = $fn->getDocComment();

        if (false === $doc) {
            return null;
        }

        $name = preg_quote($param->getName(), '/');

        // Match @param TypeName[] $paramName
        if (preg_match('/@param\s+(\S+)\[\]\s+\$' . $name . '/', $doc, $m)) {
            return self::resolveDocTypeName($m[1], $param->getDeclaringClass());
        }

        // Match @param array<TypeName> $paramName or @param list<TypeName> $paramName
        if (preg_match('/@param\s+(?:array|list)<(?:\w+,\s*)?(\S+?)>\s+\$' . $name . '/', $doc, $m)) {
            return self::resolveDocTypeName($m[1], $param->getDeclaringClass());
        }

        return null;
    }

    private static function parseVarDocType(\ReflectionProperty $prop): ?self
    {
        $doc = $prop->getDocComment();

        if (false === $doc) {
            return null;
        }

        if (preg_match('/@var\s+(\S+)\[\]/', $doc, $m)) {
            return self::resolveDocTypeName($m[1], $prop->getDeclaringClass());
        }

        if (preg_match('/@var\s+(?:array|list)<(?:\w+,\s*)?(\S+?)>/', $doc, $m)) {
            return self::resolveDocTypeName($m[1], $prop->getDeclaringClass());
        }

        return null;
    }

    /**
     * @param \ReflectionClass<object>|null $context
     */
    private static function resolveDocTypeName(string $typeName, ?\ReflectionClass $context): ?self
    {
        $resolved = match ($typeName) {
            'int', 'integer' => new self('int', true, false, null, []),
            'float', 'double' => new self('float', true, false, null, []),
            'string' => new self('string', true, false, null, []),
            'bool', 'boolean' => new self('bool', true, false, null, []),
            default => null,
        };

        if (null !== $resolved) {
            return $resolved;
        }

        // Try FQCN
        if (class_exists($typeName) || interface_exists($typeName) || enum_exists($typeName)) {
            return new self($typeName, false, false, null, []);
        }

        // Try relative to context namespace
        if (null !== $context) {
            $fqcn = $context->getNamespaceName() . '\\' . $typeName;
            if (class_exists($fqcn) || interface_exists($fqcn) || enum_exists($fqcn)) {
                return new self($fqcn, false, false, null, []);
            }
        }

        return null;
    }
}
