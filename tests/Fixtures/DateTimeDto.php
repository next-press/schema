<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class DateTimeDto
{
    public function __construct(
        public \DateTimeImmutable $createdAt,
    ) {}
}
