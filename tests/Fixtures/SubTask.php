<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class SubTask
{
    public function __construct(
        public string $title,
        public int $points,
    ) {}
}
