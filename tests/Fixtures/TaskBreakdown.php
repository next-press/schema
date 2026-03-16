<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class TaskBreakdown
{
    /**
     * @param list<SubTask> $subtasks
     */
    public function __construct(
        public array $subtasks,
        public string $reasoning,
    ) {}
}
