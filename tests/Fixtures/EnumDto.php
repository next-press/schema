<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class EnumDto
{
    /**
     * @param Priority $priority The priority level
     */
    public function __construct(
        public Priority $priority,
    ) {}
}
