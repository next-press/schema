<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

use Auroro\Schema\Attribute\With;

final readonly class ContactDto
{
    public function __construct(
        #[With(minLength: 2, maxLength: 50)]
        public string $name,
        #[With(format: 'email')]
        public string $email,
        #[With(minimum: 1, maximum: 120)]
        public int $age,
        #[With(format: 'uuid')]
        public ?string $id = null,
        #[With(pattern: '^\+\d{1,3}-\d{3,14}$')]
        public ?string $phone = null,
    ) {}
}
