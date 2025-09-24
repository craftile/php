<?php

declare(strict_types=1);

namespace Tests\Laravel\Stubs\Discovery;

use Craftile\Core\Concerns\IsBlock;
use Craftile\Core\Contracts\BlockInterface;

class DiscoveryBlock implements BlockInterface
{
    use IsBlock;

    protected static string $description = 'Another test block for discovery testing';

    public function render(): string
    {
        return '<div>Another Block</div>';
    }
}
