<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class IntArrayDto
{
    /**
     * @param int[] $numbers
     */
    public function __construct(
        public array $numbers,
    ) {}
}
