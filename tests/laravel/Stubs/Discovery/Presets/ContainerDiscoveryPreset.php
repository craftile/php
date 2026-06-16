<?php

declare(strict_types=1);

namespace Tests\Laravel\Stubs\Discovery\Presets;

use Craftile\Core\Data\BlockPreset;

class ContainerDiscoveryPreset extends BlockPreset
{
    public static function getType(): ?string
    {
        return 'container';
    }

    protected function build(): void
    {
        $this->name('Container Discovery Preset');
    }
}
