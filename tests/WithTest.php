<?php

declare(strict_types=1);

use Auroro\Schema\Attribute\With;

it('throws for non-scalar enum values', function () {
    new With(enum: [1, 'a', new \stdClass()]);
})->throws(\InvalidArgumentException::class, 'All enum values must be float, integer, strings, or null.');

it('throws for empty const string', function () {
    new With(const: '   ');
})->throws(\InvalidArgumentException::class, 'Const string must not be empty.');

it('throws for empty pattern string', function () {
    new With(pattern: '');
})->throws(\InvalidArgumentException::class, 'Pattern string must not be empty.');

it('throws for negative minLength', function () {
    new With(minLength: -1);
})->throws(\InvalidArgumentException::class, 'MinLength must be greater than or equal to 0.');

it('throws for maxLength less than minLength', function () {
    new With(minLength: 5, maxLength: 2);
})->throws(\InvalidArgumentException::class, 'MaxLength must be greater than or equal to minLength.');

it('throws for negative maxLength', function () {
    new With(maxLength: -1);
})->throws(\InvalidArgumentException::class, 'MaxLength must be greater than or equal to 0.');

it('throws for negative minimum', function () {
    new With(minimum: -1);
})->throws(\InvalidArgumentException::class, 'Minimum must be greater than or equal to 0.');

it('throws for maximum less than minimum', function () {
    new With(minimum: 10, maximum: 5);
})->throws(\InvalidArgumentException::class, 'Maximum must be greater than or equal to minimum.');

it('throws for negative maximum', function () {
    new With(maximum: -1);
})->throws(\InvalidArgumentException::class, 'Maximum must be greater than or equal to 0.');

it('throws for negative multipleOf', function () {
    new With(multipleOf: -1);
})->throws(\InvalidArgumentException::class, 'MultipleOf must be greater than or equal to 0.');

it('throws for negative exclusiveMinimum', function () {
    new With(exclusiveMinimum: -1);
})->throws(\InvalidArgumentException::class, 'ExclusiveMinimum must be greater than or equal to 0.');

it('throws for exclusiveMaximum less than exclusiveMinimum', function () {
    new With(exclusiveMinimum: 10, exclusiveMaximum: 5);
})->throws(\InvalidArgumentException::class, 'ExclusiveMaximum must be greater than or equal to exclusiveMinimum.');

it('throws for negative exclusiveMaximum', function () {
    new With(exclusiveMaximum: -1);
})->throws(\InvalidArgumentException::class, 'ExclusiveMaximum must be greater than or equal to 0.');

it('throws for negative minItems', function () {
    new With(minItems: -1);
})->throws(\InvalidArgumentException::class, 'MinItems must be greater than or equal to 0.');

it('throws for maxItems less than minItems', function () {
    new With(minItems: 5, maxItems: 2);
})->throws(\InvalidArgumentException::class, 'MaxItems must be greater than or equal to minItems.');

it('throws for negative maxItems', function () {
    new With(maxItems: -1);
})->throws(\InvalidArgumentException::class, 'MaxItems must be greater than or equal to 0.');

it('throws for uniqueItems set to false', function () {
    new With(uniqueItems: false);
})->throws(\InvalidArgumentException::class, 'UniqueItems must be true when specified.');

it('throws for negative minContains', function () {
    new With(minContains: -1);
})->throws(\InvalidArgumentException::class, 'MinContains must be greater than or equal to 0.');

it('throws for maxContains less than minContains', function () {
    new With(minContains: 5, maxContains: 2);
})->throws(\InvalidArgumentException::class, 'MaxContains must be greater than or equal to minContains.');

it('throws for negative maxContains', function () {
    new With(maxContains: -1);
})->throws(\InvalidArgumentException::class, 'MaxContains must be greater than or equal to 0.');

it('throws for negative minProperties', function () {
    new With(minProperties: -1);
})->throws(\InvalidArgumentException::class, 'MinProperties must be greater than or equal to 0.');

it('throws for maxProperties less than minProperties', function () {
    new With(minProperties: 5, maxProperties: 2);
})->throws(\InvalidArgumentException::class, 'MaxProperties must be greater than or equal to minProperties.');

it('throws for negative maxProperties', function () {
    new With(maxProperties: -1);
})->throws(\InvalidArgumentException::class, 'MaxProperties must be greater than or equal to 0.');
