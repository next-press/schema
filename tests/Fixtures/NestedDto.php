<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class NestedDto
{
    /**
     * @param string $name The name
     * @param Address $address The address
     */
    public function __construct(
        public string $name,
        public Address $address,
    ) {}
}
