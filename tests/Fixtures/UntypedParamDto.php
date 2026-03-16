<?php

declare(strict_types=1);

namespace Auroro\Schema\Tests\Fixtures;

class UntypedParamDto
{
    /** @var mixed */
    public $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }
}
