<?php

declare(strict_types=1);

namespace Auroro\Schema;

final readonly class ValidationError
{
    public function __construct(
        public string $path,
        public string $message,
        public string $constraint,
    ) {}
}
