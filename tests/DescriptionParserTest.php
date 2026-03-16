<?php

declare(strict_types=1);

use Auroro\Schema\DescriptionParser;
use Auroro\Schema\Tests\Fixtures\NoConstructorDto;
use Auroro\Schema\Tests\Fixtures\PropertyDocDto;

beforeEach(function () {
    $this->parser = new DescriptionParser();
});

it('returns description from @var on non-promoted property', function () {
    $prop = new ReflectionProperty(PropertyDocDto::class, 'label');

    expect($this->parser->getDescription($prop))->toBe('The label for display');
});

it('returns empty string when no constructor and no doc', function () {
    $prop = new ReflectionProperty(NoConstructorDto::class, 'label');

    expect($this->parser->getDescription($prop))->toBe('');
});
