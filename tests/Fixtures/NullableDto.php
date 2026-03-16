<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class NullableDto
{
    /**
     * @param ?string $name The name
     * @param ?int $count The count
     */
    public function __construct(
        public ?string $name = null,
        public ?int $count = null,
    ) {}
}
