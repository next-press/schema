<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class NullableStringUnionDto
{
    public function __construct(
        public (\Countable&\Iterator)|null $value = null,
    ) {}
}
