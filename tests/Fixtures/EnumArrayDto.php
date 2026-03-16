<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class EnumArrayDto
{
    /**
     * @param list<Priority> $priorities
     */
    public function __construct(
        public array $priorities,
    ) {}
}
