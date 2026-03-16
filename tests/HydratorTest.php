<?php

declare(strict_types=1);

use Auroro\Schema\Hydrator;
use Auroro\Schema\Tests\Fixtures\Address;
use Auroro\Schema\Tests\Fixtures\EnumArrayDto;
use Auroro\Schema\Tests\Fixtures\EnumDto;
use Auroro\Schema\Tests\Fixtures\IntArrayDto;
use Auroro\Schema\Tests\Fixtures\NestedDto;
use Auroro\Schema\Tests\Fixtures\NoConstructorDto;
use Auroro\Schema\Tests\Fixtures\NullableDto;
use Auroro\Schema\Tests\Fixtures\Priority;
use Auroro\Schema\Tests\Fixtures\ScalarDto;
use Auroro\Schema\Tests\Fixtures\SubTask;
use Auroro\Schema\Tests\Fixtures\TaskBreakdown;
use Auroro\Schema\Tests\Fixtures\UnionDto;
use Auroro\Schema\Tests\Fixtures\UntypedArrayDto;

beforeEach(function () {
    $this->hydrator = new Hydrator();
});

it('hydrates scalar DTO', function () {
    $result = $this->hydrator->hydrate([
        'name' => 'Alice',
        'count' => 5,
        'rate' => 3.14,
        'verbose' => true,
    ], ScalarDto::class);

    expect($result)->toBeInstanceOf(ScalarDto::class)
        ->and($result->name)->toBe('Alice')
        ->and($result->count)->toBe(5)
        ->and($result->rate)->toBe(3.14)
        ->and($result->verbose)->toBeTrue();
});

it('coerces string values to int and float', function () {
    $result = $this->hydrator->hydrate([
        'name' => 'Bob',
        'count' => '42',
        'rate' => '2.5',
    ], ScalarDto::class);

    expect($result->count)->toBe(42)
        ->and($result->rate)->toBe(2.5);
});

it('hydrates nullable fields', function () {
    $result = $this->hydrator->hydrate([
        'name' => null,
        'count' => null,
    ], NullableDto::class);

    expect($result)->toBeInstanceOf(NullableDto::class)
        ->and($result->name)->toBeNull()
        ->and($result->count)->toBeNull();
});

it('hydrates enum fields', function () {
    $result = $this->hydrator->hydrate([
        'priority' => 'high',
    ], EnumDto::class);

    expect($result)->toBeInstanceOf(EnumDto::class)
        ->and($result->priority)->toBe(Priority::High);
});

it('hydrates nested objects', function () {
    $result = $this->hydrator->hydrate([
        'name' => 'Alice',
        'address' => ['street' => '123 Main St', 'city' => 'Springfield'],
    ], NestedDto::class);

    expect($result)->toBeInstanceOf(NestedDto::class)
        ->and($result->address)->toBeInstanceOf(Address::class)
        ->and($result->address->street)->toBe('123 Main St')
        ->and($result->address->city)->toBe('Springfield');
});

it('hydrates array of objects', function () {
    $result = $this->hydrator->hydrate([
        'subtasks' => [
            ['title' => 'Design schema', 'points' => 3],
            ['title' => 'Write tests', 'points' => 2],
        ],
        'reasoning' => 'Broken into backend and frontend work',
    ], TaskBreakdown::class);

    expect($result)->toBeInstanceOf(TaskBreakdown::class)
        ->and($result->subtasks)->toHaveCount(2)
        ->and($result->subtasks[0])->toBeInstanceOf(SubTask::class)
        ->and($result->subtasks[0]->title)->toBe('Design schema')
        ->and($result->subtasks[0]->points)->toBe(3)
        ->and($result->subtasks[1]->title)->toBe('Write tests')
        ->and($result->reasoning)->toBe('Broken into backend and frontend work');
});

it('uses default values for missing keys', function () {
    $result = $this->hydrator->hydrate([
        'name' => 'Test',
        'count' => 1,
        'rate' => 1.0,
    ], ScalarDto::class);

    expect($result->verbose)->toBeFalse();
});

it('hydrates DateTimeImmutable from string', function () {
    $result = $this->hydrator->hydrate([
        'createdAt' => '2024-01-15T10:30:00+00:00',
    ], \Auroro\Schema\Tests\Fixtures\DateTimeDto::class);

    expect($result->createdAt)->toBeInstanceOf(\DateTimeInterface::class);
});

it('hydrates class with no constructor', function () {
    $result = $this->hydrator->hydrate([], NoConstructorDto::class);

    expect($result)->toBeInstanceOf(NoConstructorDto::class)
        ->and($result->label)->toBe('default');
});

it('passes through value for union type', function () {
    $result = $this->hydrator->hydrate(['value' => 42], UnionDto::class);

    expect($result)->toBeInstanceOf(UnionDto::class)
        ->and($result->value)->toBe(42);
});

it('coerces string false to bool false', function () {
    $result = $this->hydrator->hydrate([
        'name' => 'Test',
        'count' => 1,
        'rate' => 1.0,
        'verbose' => 'false',
    ], ScalarDto::class);

    expect($result->verbose)->toBeFalse();
});

it('coerces non-array to array', function () {
    $result = $this->hydrator->hydrate(['items' => 'single'], UntypedArrayDto::class);

    expect($result->items)->toBe(['single']);
});

it('returns array as-is when no collection type', function () {
    $result = $this->hydrator->hydrate(['items' => [1, 2, 3]], UntypedArrayDto::class);

    expect($result->items)->toBe([1, 2, 3]);
});

it('coerces array of backed enum values', function () {
    $result = $this->hydrator->hydrate(['priorities' => ['low', 'high']], EnumArrayDto::class);

    expect($result->priorities)->toBe([Priority::Low, Priority::High]);
});

it('passes through already-instantiated BackedEnum', function () {
    $result = $this->hydrator->hydrate(['priority' => Priority::High], EnumDto::class);

    expect($result->priority)->toBe(Priority::High);
});

it('passes through already-constructed object param', function () {
    $address = new Address(street: '123 Main', city: 'Springfield');
    $result = $this->hydrator->hydrate([
        'name' => 'Alice',
        'address' => $address,
    ], NestedDto::class);

    expect($result->address)->toBe($address);
});

it('coerces array of builtin types as-is', function () {
    $result = $this->hydrator->hydrate(['numbers' => [1, 2, 3]], IntArrayDto::class);

    expect($result->numbers)->toBe([1, 2, 3]);
});
