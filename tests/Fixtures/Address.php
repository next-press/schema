<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final readonly class Address
{
    /**
     * @param string $street The street
     * @param string $city The city
     */
    public function __construct(
        public string $street,
        public string $city,
    ) {}
}
