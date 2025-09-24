<?php

declare(strict_types=1);

namespace Tests\Laravel\Stubs\Discovery\Content;

use Craftile\Core\Concerns\IsBlock;
use Craftile\Core\Contracts\BlockInterface;

class TextBlock implements BlockInterface
{
    use IsBlock;

    protected static string $description = 'Text block in subdirectory';

    public function render(): string
    {
        return '<div>Text Block</div>';
    }
}
