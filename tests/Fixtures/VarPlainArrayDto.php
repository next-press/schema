<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final class VarPlainArrayDto
{
    /** @var array */
    public array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }
}
