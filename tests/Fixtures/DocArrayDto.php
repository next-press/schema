<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class DocArrayDto
{
    /**
     * @param array $items The items
     */
    public function __construct(
        public array $items,
    ) {}
}
