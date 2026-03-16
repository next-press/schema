<?php

declare(strict_types=1);

use Auroro\Result\Err;
use Auroro\Result\Ok;
use Auroro\Schema\Attribute\With;
use Auroro\Schema\SchemaProcessor;
use Auroro\Schema\Tests\Fixtures\BoolDto;
use Auroro\Schema\Tests\Fixtures\ContactDto;
use Auroro\Schema\Tests\Fixtures\ConstrainedDto;
use Auroro\Schema\Tests\Fixtures\FloatDto;
use Auroro\Schema\Tests\Fixtures\FormatDto;
use Auroro\Schema\Tests\Fixtures\MixedDto;
use Auroro\Schema\Tests\Fixtures\NoConstructorDto;
use Auroro\Schema\Tests\Fixtures\NumericConstraintsDto;
use Auroro\Schema\Tests\Fixtures\ScalarDto;
use Auroro\Schema\Tests\Fixtures\UntypedArrayDto;
use Auroro\Schema\ValidationError;

beforeEach(function () {
    $this->processor = new SchemaProcessor();
});

// --- Happy path ---

it('returns Ok with hydrated object for valid data', function () {
    $result = $this->processor->process([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'age' => 30,
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Ok::class)
        ->and($result->unwrap())->toBeInstanceOf(ContactDto::class)
        ->and($result->unwrap()->name)->toBe('Alice')
        ->and($result->unwrap()->email)->toBe('alice@example.com')
        ->and($result->unwrap()->age)->toBe(30);
});

it('returns Ok for valid email format', function () {
    $result = $this->processor->process([
        'name' => 'Alice',
        'email' => 'user@domain.co.uk',
        'age' => 25,
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Ok::class);
});

it('returns Ok for valid uuid format', function () {
    $result = $this->processor->process([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'age' => 25,
        'id' => '550e8400-e29b-41d4-a716-446655440000',
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Ok::class)
        ->and($result->unwrap()->id)->toBe('550e8400-e29b-41d4-a716-446655440000');
});

// --- Required field ---

it('returns Err for missing required field', function () {
    $result = $this->processor->process([
        'name' => 'Alice',
        // missing email and age
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class)
        ->and($result->error())->toBeArray()
        ->and($result->error())->not->toBeEmpty();

    $paths = array_map(fn (ValidationError $e) => $e->path, $result->error());
    expect($paths)->toContain('email')
        ->and($paths)->toContain('age');
});

// --- String constraints ---

it('returns Err for minLength violation', function () {
    $result = $this->processor->process([
        'name' => 'A',
        'email' => 'alice@example.com',
        'age' => 25,
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class);

    $error = $result->error()[0];
    expect($error)->toBeInstanceOf(ValidationError::class)
        ->and($error->path)->toBe('name')
        ->and($error->constraint)->toBe('minLength');
});

it('returns Err for maxLength violation', function () {
    $result = $this->processor->process([
        'name' => str_repeat('a', 51),
        'email' => 'alice@example.com',
        'age' => 25,
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class);

    $error = $result->error()[0];
    expect($error->path)->toBe('name')
        ->and($error->constraint)->toBe('maxLength');
});

it('returns Err for pattern violation', function () {
    $result = $this->processor->process([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'age' => 25,
        'phone' => 'not-a-phone',
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class);

    $error = $result->error()[0];
    expect($error->path)->toBe('phone')
        ->and($error->constraint)->toBe('pattern');
});

// --- Numeric constraints ---

it('returns Err for minimum violation', function () {
    $result = $this->processor->process([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'age' => 0,
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class);

    $error = $result->error()[0];
    expect($error->path)->toBe('age')
        ->and($error->constraint)->toBe('minimum');
});

it('returns Err for maximum violation', function () {
    $result = $this->processor->process([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'age' => 200,
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class);

    $error = $result->error()[0];
    expect($error->path)->toBe('age')
        ->and($error->constraint)->toBe('maximum');
});

// --- Format constraints ---

it('returns Err for invalid email format', function () {
    $result = $this->processor->process([
        'name' => 'Alice',
        'email' => 'not-an-email',
        'age' => 25,
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class);

    $error = $result->error()[0];
    expect($error->path)->toBe('email')
        ->and($error->constraint)->toBe('format')
        ->and($error->message)->toContain('email');
});

it('returns Err for invalid uuid format', function () {
    $result = $this->processor->process([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'age' => 25,
        'id' => 'not-a-uuid',
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class);

    $error = $result->error()[0];
    expect($error->path)->toBe('id')
        ->and($error->constraint)->toBe('format');
});

// --- ValidationError structure ---

it('ValidationError has correct path, message, and constraint', function () {
    $result = $this->processor->process([
        'name' => 'A',
        'email' => 'alice@example.com',
        'age' => 25,
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class);

    $error = $result->error()[0];
    expect($error)->toBeInstanceOf(ValidationError::class)
        ->and($error->path)->toBe('name')
        ->and($error->message)->toContain('2')
        ->and($error->constraint)->toBe('minLength');
});

// --- Multiple errors ---

it('collects multiple validation errors', function () {
    $result = $this->processor->process([
        'name' => 'A',
        'email' => 'bad',
        'age' => 200,
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class)
        ->and($result->error())->toHaveCount(3);
});

// --- Type validation ---

it('returns Err for wrong type', function () {
    $result = $this->processor->process([
        'name' => 123,
        'email' => 'alice@example.com',
        'age' => 25,
    ], ContactDto::class);

    expect($result)->toBeInstanceOf(Err::class);

    $error = $result->error()[0];
    expect($error->path)->toBe('name')
        ->and($error->constraint)->toBe('type');
});

// --- With attribute format parameter ---

it('With accepts valid format parameter', function () {
    $with = new With(format: 'email');
    expect($with->format)->toBe('email');
});

it('With throws for unknown format', function () {
    new With(format: 'unknown');
})->throws(\InvalidArgumentException::class);

// --- Constrained DTO (existing fixture) ---

it('validates constrained DTO with pattern and range', function () {
    $result = $this->processor->process([
        'count' => 5,
        'name' => 'hello',
    ], ConstrainedDto::class);

    expect($result)->toBeInstanceOf(Ok::class)
        ->and($result->unwrap())->toBeInstanceOf(ConstrainedDto::class)
        ->and($result->unwrap()->count)->toBe(5)
        ->and($result->unwrap()->name)->toBe('hello');
});

it('returns Err for constrained DTO pattern violation', function () {
    $result = $this->processor->process([
        'count' => 5,
        'name' => 'UPPER',
    ], ConstrainedDto::class);

    expect($result)->toBeInstanceOf(Err::class);

    $error = $result->error()[0];
    expect($error->constraint)->toBe('pattern');
});

// --- No constructor ---

it('returns Ok for class with no constructor', function () {
    $result = $this->processor->process([], NoConstructorDto::class);

    expect($result)->toBeInstanceOf(Ok::class);
});

// --- Float type validation ---

it('returns Err for non-numeric float field', function () {
    $result = $this->processor->process(['rate' => 'abc'], FloatDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('type');
});

it('returns Ok for numeric string float field', function () {
    $result = $this->processor->process(['rate' => '3.14'], FloatDto::class);

    expect($result)->toBeInstanceOf(Ok::class);
});

// --- Array type validation ---

it('returns Err for non-array array field', function () {
    $result = $this->processor->process(['items' => 'not-array'], UntypedArrayDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('type');
});

// --- Exclusive numeric constraints ---

it('returns Err for exclusiveMinimum violation', function () {
    $result = $this->processor->process([
        'score' => 0,
        'step' => 5,
        'tags' => ['a'],
        'choice' => 'a',
    ], NumericConstraintsDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('exclusiveMinimum');
});

it('returns Err for exclusiveMaximum violation', function () {
    $result = $this->processor->process([
        'score' => 100,
        'step' => 5,
        'tags' => ['a'],
        'choice' => 'a',
    ], NumericConstraintsDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('exclusiveMaximum');
});

it('returns Err for multipleOf violation', function () {
    $result = $this->processor->process([
        'score' => 50,
        'step' => 3,
        'tags' => ['a'],
        'choice' => 'a',
    ], NumericConstraintsDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('multipleOf');
});

// --- Array constraints ---

it('returns Err for minItems violation', function () {
    $result = $this->processor->process([
        'score' => 50,
        'step' => 5,
        'tags' => [],
        'choice' => 'a',
    ], NumericConstraintsDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('minItems');
});

it('returns Err for maxItems violation', function () {
    $result = $this->processor->process([
        'score' => 50,
        'step' => 5,
        'tags' => ['a', 'b', 'c', 'd', 'e', 'f'],
        'choice' => 'a',
    ], NumericConstraintsDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('maxItems');
});

it('returns Err for uniqueItems violation', function () {
    $result = $this->processor->process([
        'score' => 50,
        'step' => 5,
        'tags' => ['a', 'a'],
        'choice' => 'a',
    ], NumericConstraintsDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('uniqueItems');
});

// --- Enum constraint ---

it('returns Err for enum violation', function () {
    $result = $this->processor->process([
        'score' => 50,
        'step' => 5,
        'tags' => ['a'],
        'choice' => 'd',
    ], NumericConstraintsDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('enum');
});

// --- Format validators ---

it('returns Err for invalid uri format', function () {
    $result = $this->processor->process([
        'website' => 'not-a-uri',
        'timestamp' => '2024-01-15T10:30:00+00:00',
        'date' => '2024-01-15',
        'time' => '10:30:00',
        'ipv4' => '127.0.0.1',
        'ipv6' => '::1',
        'host' => 'example.com',
    ], FormatDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('format')
        ->and($error->message)->toContain('uri');
});

it('returns Err for invalid date-time format', function () {
    $result = $this->processor->process([
        'website' => 'https://example.com',
        'timestamp' => 'not-datetime',
        'date' => '2024-01-15',
        'time' => '10:30:00',
        'ipv4' => '127.0.0.1',
        'ipv6' => '::1',
        'host' => 'example.com',
    ], FormatDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $errors = $result->error();
    $constraints = array_map(fn(ValidationError $e) => $e->path, $errors);
    expect($constraints)->toContain('timestamp');
});

it('returns Err for invalid date format', function () {
    $result = $this->processor->process([
        'website' => 'https://example.com',
        'timestamp' => '2024-01-15T10:30:00+00:00',
        'date' => 'not-date',
        'time' => '10:30:00',
        'ipv4' => '127.0.0.1',
        'ipv6' => '::1',
        'host' => 'example.com',
    ], FormatDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $errors = $result->error();
    $paths = array_map(fn(ValidationError $e) => $e->path, $errors);
    expect($paths)->toContain('date');
});

it('returns Err for invalid time format', function () {
    $result = $this->processor->process([
        'website' => 'https://example.com',
        'timestamp' => '2024-01-15T10:30:00+00:00',
        'date' => '2024-01-15',
        'time' => 'not-time',
        'ipv4' => '127.0.0.1',
        'ipv6' => '::1',
        'host' => 'example.com',
    ], FormatDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $errors = $result->error();
    $paths = array_map(fn(ValidationError $e) => $e->path, $errors);
    expect($paths)->toContain('time');
});

it('returns Err for invalid ipv4 format', function () {
    $result = $this->processor->process([
        'website' => 'https://example.com',
        'timestamp' => '2024-01-15T10:30:00+00:00',
        'date' => '2024-01-15',
        'time' => '10:30:00',
        'ipv4' => 'not-ip',
        'ipv6' => '::1',
        'host' => 'example.com',
    ], FormatDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $errors = $result->error();
    $paths = array_map(fn(ValidationError $e) => $e->path, $errors);
    expect($paths)->toContain('ipv4');
});

it('returns Err for invalid ipv6 format', function () {
    $result = $this->processor->process([
        'website' => 'https://example.com',
        'timestamp' => '2024-01-15T10:30:00+00:00',
        'date' => '2024-01-15',
        'time' => '10:30:00',
        'ipv4' => '127.0.0.1',
        'ipv6' => 'not-ipv6',
        'host' => 'example.com',
    ], FormatDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $errors = $result->error();
    $paths = array_map(fn(ValidationError $e) => $e->path, $errors);
    expect($paths)->toContain('ipv6');
});

it('returns Err for invalid hostname format', function () {
    $result = $this->processor->process([
        'website' => 'https://example.com',
        'timestamp' => '2024-01-15T10:30:00+00:00',
        'date' => '2024-01-15',
        'time' => '10:30:00',
        'ipv4' => '127.0.0.1',
        'ipv6' => '::1',
        'host' => '!!!',
    ], FormatDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $errors = $result->error();
    $paths = array_map(fn(ValidationError $e) => $e->path, $errors);
    expect($paths)->toContain('host');
});

// --- Bool type validation ---

it('returns Err for non-bool value in bool field', function () {
    $result = $this->processor->process(['flag' => 'not-a-bool'], BoolDto::class);

    expect($result)->toBeInstanceOf(Err::class);
    $error = $result->error()[0];
    expect($error->constraint)->toBe('type');
});

// --- Mixed type validation (default arm) ---

it('returns Ok for mixed type field with any value', function () {
    $result = $this->processor->process(['data' => 'anything'], MixedDto::class);

    expect($result)->toBeInstanceOf(Ok::class);
});
