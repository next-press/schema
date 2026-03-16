<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class FqcnArrayDto
{
    /**
     * @param \Auroro\Schema\Tests\Fixtures\SubTask[] $tasks
     */
    public function __construct(
        public array $tasks,
    ) {}
}
