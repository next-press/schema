<?php

declare(strict_types=1);

use Auroro\Schema\DataViewsFieldMapper;

it('maps string field to text type', function (): void {
    $mapper = new DataViewsFieldMapper();
    $fields = $mapper->map([
        'type' => 'object',
        'properties' => ['title' => ['type' => 'string']],
        'required' => ['title'],
        'additionalProperties' => false,
    ]);

    expect($fields)->toHaveCount(1);
    expect($fields[0]['id'])->toBe('title');
    expect($fields[0]['type'])->toBe('text');
    expect($fields[0]['label'])->toBe('Title');
    expect($fields[0]['enableGlobalSearch'])->toBeTrue();
    expect($fields[0]['isValid'])->toBe(['required' => true]);
});

it('maps boolean field', function (): void {
    $mapper = new DataViewsFieldMapper();
    $fields = $mapper->map([
        'type' => 'object',
        'properties' => ['completed' => ['type' => 'boolean']],
        'required' => [],
        'additionalProperties' => false,
    ]);

    expect($fields[0]['type'])->toBe('boolean');
    expect($fields[0]['enableGlobalSearch'])->toBeFalse();
});

it('maps integer and number fields', function (): void {
    $mapper = new DataViewsFieldMapper();
    $fields = $mapper->map([
        'type' => 'object',
        'properties' => [
            'count' => ['type' => 'integer'],
            'price' => ['type' => 'number'],
        ],
        'required' => [],
        'additionalProperties' => false,
    ]);

    expect($fields[0]['type'])->toBe('integer');
    expect($fields[1]['type'])->toBe('number');
});

it('maps nullable type correctly', function (): void {
    $mapper = new DataViewsFieldMapper();
    $fields = $mapper->map([
        'type' => 'object',
        'properties' => ['project' => ['type' => ['string', 'null']]],
        'required' => [],
        'additionalProperties' => false,
    ]);

    expect($fields[0]['type'])->toBe('text');
    expect($fields[0])->not->toHaveKey('isValid');
});

it('maps enum to text with elements', function (): void {
    $mapper = new DataViewsFieldMapper();
    $fields = $mapper->map([
        'type' => 'object',
        'properties' => [
            'status' => ['type' => 'string', 'enum' => ['active', 'completed', 'archived']],
        ],
        'required' => [],
        'additionalProperties' => false,
    ]);

    expect($fields[0]['type'])->toBe('text');
    expect($fields[0]['elements'])->toHaveCount(3);
    expect($fields[0]['elements'][0])->toBe(['value' => 'active', 'label' => 'Active']);
    expect($fields[0]['elements'][1])->toBe(['value' => 'completed', 'label' => 'Completed']);
});

it('maps datetime format', function (): void {
    $mapper = new DataViewsFieldMapper();
    $fields = $mapper->map([
        'type' => 'object',
        'properties' => ['createdAt' => ['type' => 'string', 'format' => 'date-time']],
        'required' => [],
        'additionalProperties' => false,
    ]);

    expect($fields[0]['type'])->toBe('datetime');
    expect($fields[0]['label'])->toBe('Created At');
});

it('humanizes camelCase and snake_case field names', function (): void {
    $mapper = new DataViewsFieldMapper();
    $fields = $mapper->map([
        'type' => 'object',
        'properties' => [
            'firstName' => ['type' => 'string'],
            'last_name' => ['type' => 'string'],
        ],
        'required' => [],
        'additionalProperties' => false,
    ]);

    expect($fields[0]['label'])->toBe('First Name');
    expect($fields[1]['label'])->toBe('Last name');
});

it('includes validation constraints from schema', function (): void {
    $mapper = new DataViewsFieldMapper();
    $fields = $mapper->map([
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 100],
            'age' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 150],
        ],
        'required' => ['name'],
        'additionalProperties' => false,
    ]);

    expect($fields[0]['isValid'])->toBe(['required' => true, 'minLength' => 3, 'maxLength' => 100]);
    expect($fields[1]['isValid'])->toBe(['min' => 0, 'max' => 150]);
});

it('includes description when present', function (): void {
    $mapper = new DataViewsFieldMapper();
    $fields = $mapper->map([
        'type' => 'object',
        'properties' => ['email' => ['type' => 'string', 'format' => 'email', 'description' => 'Primary email address']],
        'required' => [],
        'additionalProperties' => false,
    ]);

    expect($fields[0]['description'])->toBe('Primary email address');
    expect($fields[0]['type'])->toBe('email');
});
