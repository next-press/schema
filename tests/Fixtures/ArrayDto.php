<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class ArrayDto
{
    /**
     * @param string[] $tags The tags
     */
    public function __construct(
        public array $tags,
    ) {}
}
