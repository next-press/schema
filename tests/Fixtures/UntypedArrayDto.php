<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class UntypedArrayDto
{
    public function __construct(
        public array $items,
    ) {}
}
