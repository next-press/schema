<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

use Auroro\Schema\Attribute\With;

final readonly class NumericConstraintsDto
{
    public function __construct(
        #[With(exclusiveMinimum: 0, exclusiveMaximum: 100)]
        public int $score,
        #[With(multipleOf: 5)]
        public int $step,
        #[With(minItems: 1, maxItems: 5, uniqueItems: true)]
        public array $tags,
        #[With(enum: ['a', 'b', 'c'])]
        public string $choice,
    ) {}
}
