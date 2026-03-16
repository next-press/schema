<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class FloatArrayDto
{
    /**
     * @param float[] $values
     */
    public function __construct(
        public array $values,
    ) {}
}
