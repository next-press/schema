<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final class VarDocArrayDto
{
    /** @var string[] */
    public array $tags;

    public function __construct(array $tags)
    {
        $this->tags = $tags;
    }
}
