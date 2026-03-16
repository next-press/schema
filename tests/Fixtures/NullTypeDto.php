<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class NullTypeDto
{
    public function __construct(
        public null $value = null,
    ) {}
}
