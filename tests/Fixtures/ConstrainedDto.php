<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

use Auroro\Schema\Attribute\With;

final readonly class ConstrainedDto
{
    /**
     * @param int $count The count
     * @param string $name The name
     */
    public function __construct(
        #[With(minimum: 0, maximum: 10)]
        public int $count,
        #[With(pattern: '^[a-z]+$')]
        public string $name,
    ) {}
}
