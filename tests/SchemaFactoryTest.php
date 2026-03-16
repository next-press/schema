<?php

declare(strict_types=1);

use Auroro\Schema\SchemaFactory;
use Auroro\Schema\Tests\Fixtures\ArrayDto;
use Auroro\Schema\Tests\Fixtures\ConstrainedDto;
use Auroro\Schema\Tests\Fixtures\DateTimeDto;
use Auroro\Schema\Tests\Fixtures\EmptyDto;
use Auroro\Schema\Tests\Fixtures\EnumDto;
use Auroro\Schema\Tests\Fixtures\MixedDto;
use Auroro\Schema\Tests\Fixtures\NestedDto;
use Auroro\Schema\Tests\Fixtures\NullableDto;
use Auroro\Schema\Tests\Fixtures\NullableUnionDto;
use Auroro\Schema\Tests\Fixtures\NullTypeDto;
use Auroro\Schema\Tests\Fixtures\ScalarDto;
use Auroro\Schema\Tests\Fixtures\SubTask;
use Auroro\Schema\Tests\Fixtures\TaskBreakdown;
use Auroro\Schema\Tests\Fixtures\UnionDto;
use Auroro\Schema\Tests\Fixtures\UntypedArrayDto;

beforeEach(function () {
    $this->factory = new SchemaFactory();
});

it('builds schema for scalar types', function () {
    $schema = $this->factory->buildParameters(ScalarDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['name']['type'])->toBe('string')
        ->and($schema['properties']['count']['type'])->toBe('integer')
        ->and($schema['properties']['rate']['type'])->toBe('number')
        ->and($schema['properties']['verbose']['type'])->toBe('boolean')
        ->and($schema['required'])->toBe(['name', 'count', 'rate']);
});

it('builds schema for nullable types', function () {
    $schema = $this->factory->buildParameters(NullableDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['name']['type'])->toBe(['string', 'null'])
        ->and($schema['properties']['count']['type'])->toBe(['integer', 'null'])
        ->and($schema['required'])->toBe([]);
});

it('builds schema for array with typed items', function () {
    $schema = $this->factory->buildParameters(ArrayDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['tags']['type'])->toBe('array')
        ->and($schema['properties']['tags']['items'])->toBe(['type' => 'string']);
});

it('builds schema for nested object properties', function () {
    $schema = $this->factory->buildParameters(NestedDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['address']['type'])->toBe('object')
        ->and($schema['properties']['address']['properties']['street']['type'])->toBe('string')
        ->and($schema['properties']['address']['properties']['city']['type'])->toBe('string');
});

it('builds schema for backed enum properties', function () {
    $schema = $this->factory->buildParameters(EnumDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['priority']['type'])->toBe('string')
        ->and($schema['properties']['priority']['enum'])->toBe(['low', 'medium', 'high']);
});

it('builds schema with With constraints', function () {
    $schema = $this->factory->buildParameters(ConstrainedDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['count']['minimum'])->toBe(0)
        ->and($schema['properties']['count']['maximum'])->toBe(10)
        ->and($schema['properties']['name']['pattern'])->toBe('^[a-z]+$');
});

it('builds schema for DateTimeInterface as string format', function () {
    $schema = $this->factory->buildParameters(DateTimeDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['createdAt']['type'])->toBe('string')
        ->and($schema['properties']['createdAt']['format'])->toBe('date-time');
});

it('returns null for empty class', function () {
    $schema = $this->factory->buildParameters(EmptyDto::class, '__construct');

    expect($schema)->toBeNull();
});

it('includes descriptions from phpdoc', function () {
    $schema = $this->factory->buildParameters(ScalarDto::class, '__construct');

    expect($schema['properties']['name']['description'])->toBe('The name')
        ->and($schema['properties']['count']['description'])->toBe('The count');
});

it('builds properties schema from class for DTO output', function () {
    $schema = $this->factory->buildProperties(ScalarDto::class);

    expect($schema)->not->toBeNull()
        ->and($schema['type'])->toBe('object')
        ->and($schema['properties']['name']['type'])->toBe('string')
        ->and($schema['properties']['count']['type'])->toBe('integer');
});

it('builds properties schema for nested DTO with array of objects', function () {
    $schema = $this->factory->buildProperties(TaskBreakdown::class);

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['subtasks']['type'])->toBe('array')
        ->and($schema['properties']['subtasks']['items']['type'])->toBe('object')
        ->and($schema['properties']['subtasks']['items']['properties']['title']['type'])->toBe('string')
        ->and($schema['properties']['subtasks']['items']['properties']['points']['type'])->toBe('integer')
        ->and($schema['properties']['reasoning']['type'])->toBe('string');
});

it('builds anyOf schema for union type', function () {
    $schema = $this->factory->buildParameters(UnionDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['value'])->toHaveKey('anyOf')
        ->and($schema['properties']['value']['anyOf'])->toHaveCount(2);
});

it('builds null type schema for standalone null parameter', function () {
    $schema = $this->factory->buildParameters(NullTypeDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['value']['type'])->toContain('null');
});

it('builds nullable union schema with anyOf', function () {
    $schema = $this->factory->buildParameters(NullableUnionDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['value'])->toHaveKey('anyOf')
        ->and($schema['properties']['value']['anyOf'])->toHaveCount(2);
});

it('builds array with no item type defaulting to string', function () {
    $schema = $this->factory->buildParameters(UntypedArrayDto::class, '__construct');

    expect($schema)->not->toBeNull()
        ->and($schema['properties']['items']['type'])->toBe('array')
        ->and($schema['properties']['items']['items'])->toBe(['type' => 'string']);
});

it('throws for mixed type', function () {
    $this->factory->buildParameters(MixedDto::class, '__construct');
})->throws(\InvalidArgumentException::class, 'Cannot build schema from plain object type.');
