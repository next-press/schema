<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class NullableUnionDto
{
    public function __construct(
        public string|int|null $value = null,
    ) {}
}
