<?php

declare(strict_types=1);

use Auroro\Schema\ReflectedType;
use Auroro\Schema\Tests\Fixtures\ArrayGenericDocDto;
use Auroro\Schema\Tests\Fixtures\BoolArrayDto;
use Auroro\Schema\Tests\Fixtures\DocArrayDto;
use Auroro\Schema\Tests\Fixtures\FloatArrayDto;
use Auroro\Schema\Tests\Fixtures\FqcnArrayDto;
use Auroro\Schema\Tests\Fixtures\IntersectionDto;
use Auroro\Schema\Tests\Fixtures\NullableStringUnionDto;
use Auroro\Schema\Tests\Fixtures\NullableUnionDto;
use Auroro\Schema\Tests\Fixtures\Priority;
use Auroro\Schema\Tests\Fixtures\ScalarDto;
use Auroro\Schema\Tests\Fixtures\SubTask;
use Auroro\Schema\Tests\Fixtures\UnionDto;
use Auroro\Schema\Tests\Fixtures\UnresolvableArrayDto;
use Auroro\Schema\Tests\Fixtures\UntypedArrayDto;
use Auroro\Schema\Tests\Fixtures\UntypedParamDto;
use Auroro\Schema\Tests\Fixtures\VarDocArrayDto;
use Auroro\Schema\Tests\Fixtures\VarPlainArrayDto;

it('resolves builtin types from parameter', function () {
    $param = new ReflectionParameter([ScalarDto::class, '__construct'], 'name');
    $type = ReflectedType::fromParameter($param);

    expect($type->name)->toBe('string')
        ->and($type->isBuiltin)->toBeTrue()
        ->and($type->isNullable)->toBeFalse();
});

it('resolves nullable types', function () {
    $param = new ReflectionParameter([Auroro\Schema\Tests\Fixtures\NullableDto::class, '__construct'], 'name');
    $type = ReflectedType::fromParameter($param);

    expect($type->name)->toBe('string')
        ->and($type->isNullable)->toBeTrue();
});

it('detects backed enum', function () {
    $param = new ReflectionParameter([Auroro\Schema\Tests\Fixtures\EnumDto::class, '__construct'], 'priority');
    $type = ReflectedType::fromParameter($param);

    expect($type->isBackedEnum())->toBeTrue()
        ->and($type->isObject())->toBeFalse();
});

it('detects object type', function () {
    $param = new ReflectionParameter([Auroro\Schema\Tests\Fixtures\NestedDto::class, '__construct'], 'address');
    $type = ReflectedType::fromParameter($param);

    expect($type->isObject())->toBeTrue()
        ->and($type->isBackedEnum())->toBeFalse();
});

it('parses array doc type from parameter', function () {
    $param = new ReflectionParameter([Auroro\Schema\Tests\Fixtures\ArrayDto::class, '__construct'], 'tags');
    $type = ReflectedType::fromParameter($param);

    expect($type->name)->toBe('array')
        ->and($type->collectionValueType)->not->toBeNull()
        ->and($type->collectionValueType->name)->toBe('string');
});

it('resolves from property', function () {
    $prop = new ReflectionProperty(Auroro\Schema\Tests\Fixtures\ScalarDto::class, 'name');
    $type = ReflectedType::fromProperty($prop);

    expect($type->name)->toBe('string')
        ->and($type->isBuiltin)->toBeTrue();
});

it('unwrapNullable returns self when not nullable', function () {
    $param = new ReflectionParameter([ScalarDto::class, '__construct'], 'name');
    $type = ReflectedType::fromParameter($param);

    expect($type->unwrapNullable())->toBe($type);
});

it('returns mixed for untyped parameter', function () {
    $param = new ReflectionParameter([UntypedParamDto::class, '__construct'], 'value');
    $type = ReflectedType::fromParameter($param);

    expect($type->name)->toBe('mixed')
        ->and($type->isBuiltin)->toBeTrue()
        ->and($type->isNullable)->toBeTrue();
});

it('handles union type with multiple variants', function () {
    $param = new ReflectionParameter([UnionDto::class, '__construct'], 'value');
    $type = ReflectedType::fromParameter($param);

    expect($type->unionTypes)->not->toBeEmpty()
        ->and($type->isNullable)->toBeFalse();
});

it('handles nullable union type', function () {
    $param = new ReflectionParameter([NullableUnionDto::class, '__construct'], 'value');
    $type = ReflectedType::fromParameter($param);

    expect($type->isNullable)->toBeTrue()
        ->and($type->unionTypes)->not->toBeEmpty();
});

it('unwrapNullable filters null from nullable union', function () {
    $param = new ReflectionParameter([NullableUnionDto::class, '__construct'], 'value');
    $type = ReflectedType::fromParameter($param);
    $unwrapped = $type->unwrapNullable();

    // Should return self since there are multiple non-null variants
    expect($unwrapped->unionTypes)->not->toBeEmpty();
});

it('returns mixed for intersection type', function () {
    $param = new ReflectionParameter([IntersectionDto::class, '__construct'], 'value');
    $type = ReflectedType::fromParameter($param);

    expect($type->name)->toBe('mixed')
        ->and($type->isBuiltin)->toBeTrue();
});

it('returns null when no docblock on function', function () {
    $param = new ReflectionParameter([UntypedArrayDto::class, '__construct'], 'items');
    $type = ReflectedType::fromParameter($param);

    expect($type->name)->toBe('array')
        ->and($type->collectionValueType)->toBeNull();
});

it('parses @var Type[] from property', function () {
    $prop = new ReflectionProperty(VarDocArrayDto::class, 'tags');
    $type = ReflectedType::fromProperty($prop);

    expect($type->name)->toBe('array')
        ->and($type->collectionValueType)->not->toBeNull()
        ->and($type->collectionValueType->name)->toBe('string');
});

it('parses @var array<Type> from property', function () {
    $prop = new ReflectionProperty(ArrayGenericDocDto::class, 'items');
    $type = ReflectedType::fromProperty($prop);

    expect($type->name)->toBe('array')
        ->and($type->collectionValueType)->not->toBeNull()
        ->and($type->collectionValueType->name)->toBe('string');
});

it('handles nullable union with single non-null variant via DNF', function () {
    $param = new ReflectionParameter([NullableStringUnionDto::class, '__construct'], 'value');
    $type = ReflectedType::fromParameter($param);

    expect($type->isNullable)->toBeTrue()
        ->and($type->unionTypes)->not->toBeEmpty();
});

it('returns null for array param with plain @param array doc', function () {
    $param = new ReflectionParameter([DocArrayDto::class, '__construct'], 'items');
    $type = ReflectedType::fromParameter($param);

    expect($type->name)->toBe('array')
        ->and($type->collectionValueType)->toBeNull();
});

it('returns null for @var array property with no item type', function () {
    $prop = new ReflectionProperty(VarPlainArrayDto::class, 'items');
    $type = ReflectedType::fromProperty($prop);

    expect($type->name)->toBe('array')
        ->and($type->collectionValueType)->toBeNull();
});

it('resolves float[] doc type', function () {
    $param = new ReflectionParameter([FloatArrayDto::class, '__construct'], 'values');
    $type = ReflectedType::fromParameter($param);

    expect($type->collectionValueType)->not->toBeNull()
        ->and($type->collectionValueType->name)->toBe('float');
});

it('resolves bool[] doc type', function () {
    $param = new ReflectionParameter([BoolArrayDto::class, '__construct'], 'flags');
    $type = ReflectedType::fromParameter($param);

    expect($type->collectionValueType)->not->toBeNull()
        ->and($type->collectionValueType->name)->toBe('bool');
});

it('resolves FQCN class in doc type', function () {
    $param = new ReflectionParameter([FqcnArrayDto::class, '__construct'], 'tasks');
    $type = ReflectedType::fromParameter($param);

    expect($type->collectionValueType)->not->toBeNull()
        ->and($type->collectionValueType->name)->toBe('\\' . SubTask::class);
});

it('returns null for unresolvable doc type', function () {
    $param = new ReflectionParameter([UnresolvableArrayDto::class, '__construct'], 'items');
    $type = ReflectedType::fromParameter($param);

    expect($type->name)->toBe('array')
        ->and($type->collectionValueType)->toBeNull();
});
