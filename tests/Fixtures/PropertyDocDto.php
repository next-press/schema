<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

final class PropertyDocDto
{
    /** @var string The label for display */
    public string $label;

    public function __construct(string $label)
    {
        $this->label = $label;
    }
}
