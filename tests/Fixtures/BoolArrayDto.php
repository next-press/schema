<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class BoolArrayDto
{
    /**
     * @param bool[] $flags
     */
    public function __construct(
        public array $flags,
    ) {}
}
