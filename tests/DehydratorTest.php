<?php

declare(strict_types=1);

use Auroro\Schema\Dehydrator;
use Auroro\Schema\Hydrator;
use Auroro\Schema\Tests\Fixtures\Address;
use Auroro\Schema\Tests\Fixtures\EnumDto;
use Auroro\Schema\Tests\Fixtures\NestedDto;
use Auroro\Schema\Tests\Fixtures\Priority;
use Auroro\Schema\Tests\Fixtures\ScalarDto;
use Auroro\Schema\Tests\Fixtures\Status;
use Auroro\Schema\Tests\Fixtures\SubTask;
use Auroro\Schema\Tests\Fixtures\TaskBreakdown;
use Auroro\Schema\Tests\Fixtures\UnitEnumDto;

beforeEach(function () {
    $this->dehydrator = new Dehydrator();
    $this->hydrator = new Hydrator();
});

it('dehydrates scalar DTO to array', function () {
    $dto = new ScalarDto(name: 'Alice', count: 5, rate: 3.14, verbose: true);

    $data = $this->dehydrator->dehydrate($dto);

    expect($data)->toBe([
        'name' => 'Alice',
        'count' => 5,
        'rate' => 3.14,
        'verbose' => true,
    ]);
});

it('dehydrates enum fields to backed values', function () {
    $dto = new EnumDto(priority: Priority::High);

    $data = $this->dehydrator->dehydrate($dto);

    expect($data)->toBe(['priority' => 'high']);
});

it('dehydrates nested objects recursively', function () {
    $dto = new NestedDto(
        name: 'Alice',
        address: new Address(street: '123 Main St', city: 'Springfield'),
    );

    $data = $this->dehydrator->dehydrate($dto);

    expect($data)->toBe([
        'name' => 'Alice',
        'address' => ['street' => '123 Main St', 'city' => 'Springfield'],
    ]);
});

it('dehydrates arrays of objects', function () {
    $dto = new TaskBreakdown(
        subtasks: [
            new SubTask(title: 'Design', points: 3),
            new SubTask(title: 'Build', points: 5),
        ],
        reasoning: 'Split by concern',
    );

    $data = $this->dehydrator->dehydrate($dto);

    expect($data)->toBe([
        'subtasks' => [
            ['title' => 'Design', 'points' => 3],
            ['title' => 'Build', 'points' => 5],
        ],
        'reasoning' => 'Split by concern',
    ]);
});

it('roundtrips through dehydrate then hydrate', function () {
    $original = new TaskBreakdown(
        subtasks: [
            new SubTask(title: 'Design', points: 3),
            new SubTask(title: 'Build', points: 5),
        ],
        reasoning: 'Split by concern',
    );

    $data = $this->dehydrator->dehydrate($original);
    $restored = $this->hydrator->hydrate($data, TaskBreakdown::class);

    expect($restored)->toEqual($original);
});

it('dehydrates unit enum to name string', function () {
    $dto = new UnitEnumDto(status: Status::Active);

    $data = $this->dehydrator->dehydrate($dto);

    expect($data)->toBe(['status' => 'Active']);
});
