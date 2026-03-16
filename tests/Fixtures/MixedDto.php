<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class MixedDto
{
    public function __construct(
        public mixed $data,
    ) {}
}
