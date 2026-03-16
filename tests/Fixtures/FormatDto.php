<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

use Auroro\Schema\Attribute\With;

final readonly class FormatDto
{
    public function __construct(
        #[With(format: 'uri')]
        public string $website,
        #[With(format: 'date-time')]
        public string $timestamp,
        #[With(format: 'date')]
        public string $date,
        #[With(format: 'time')]
        public string $time,
        #[With(format: 'ipv4')]
        public string $ipv4,
        #[With(format: 'ipv6')]
        public string $ipv6,
        #[With(format: 'hostname')]
        public string $host,
    ) {}
}
