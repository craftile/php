<?php

declare(strict_types=1);

namespace Tests\Stubs\Discovery;

class RegularClass
{
    public function someMethod(): string
    {
        return 'This is not a block';
    }
}