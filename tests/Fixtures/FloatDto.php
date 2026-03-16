<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class FloatDto
{
    public function __construct(
        public float $rate,
    ) {}
}
