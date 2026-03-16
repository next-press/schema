<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class ScalarDto
{
    /**
     * @param string $name The name
     * @param int $count The count
     * @param float $rate The rate
     * @param bool $verbose Verbose output
     */
    public function __construct(
        public string $name,
        public int $count,
        public float $rate,
        public bool $verbose = false,
    ) {}
}
