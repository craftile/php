<?php

declare(strict_types=1);

namespace Tests\Laravel\Stubs\Discovery;

use Craftile\Core\Concerns\IsBlock;
use Craftile\Core\Contracts\BlockInterface;

class StubTestBlock implements BlockInterface
{
    use IsBlock;

    protected static string $description = 'A test block for discovery testing';

    public function render(): string
    {
        return '<div>Test Block</div>';
    }
}
