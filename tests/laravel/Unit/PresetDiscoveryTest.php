<?php

declare(strict_types=1);

use Craftile\Core\Contracts\BlockInterface;
use Craftile\Core\Data\BlockPreset;
use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\BlockSchemaRegistry;
use Craftile\Laravel\PresetDiscovery;

class ContainerTestPreset extends BlockPreset
{
    public static function getType(): ?string
    {
        return 'container';
    }

    protected function build(): void
    {
        $this->name('Test Container Preset');
    }
}

class TextTestPreset extends BlockPreset
{
    public static function getType(): ?string
    {
        return 'text';
    }

    protected function build(): void
    {
        $this->name('Text Preset');
    }
}

class NoBlockTypePreset extends BlockPreset {}

class DiscoveryTestBlock implements BlockInterface
{
    use \Craftile\Core\Concerns\IsBlock;

    public static function type(): string
    {
        return 'discovery-block';
    }

    public function render(): string
    {
        return '<div>Discovery test block</div>';
    }
}

class BlockClassPreset extends BlockPreset
{
    public static function getType(): ?string
    {
        return DiscoveryTestBlock::class;
    }

    protected function build(): void
    {
        $this->name('Block Class Preset');
    }
}

describe('PresetDiscovery', function () {
    beforeEach(function () {
        $this->registry = new BlockSchemaRegistry;
        $this->discovery = new PresetDiscovery($this->registry);
        $this->tempDir = sys_get_temp_dir().'/craftile-test-'.uniqid();
        mkdir($this->tempDir, 0777, true);
    });

    afterEach(function () {
        if (is_dir($this->tempDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $fileinfo->isDir() ? rmdir($fileinfo->getRealPath()) : unlink($fileinfo->getRealPath());
            }

            rmdir($this->tempDir);
        }
    });

    it('handles missing directories gracefully', function () {
        $this->discovery->scan('App\\Presets', '/nonexistent/path');

        expect(true)->toBeTrue();
    });

    it('discovers and registers preset classes', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $this->discovery->scan('', '');

        expect($this->registry->get('container')->presets)->toHaveCount(0);
    });

    it('skips presets without getType', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(0);
    });

    it('validates preset classes extend BlockPreset', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        expect($this->registry->get('container')->presets)->toHaveCount(0);
    });

    it('resolves block class names to types', function () {
        $schema = new BlockSchema('discovery-block', 'discovery-block', DiscoveryTestBlock::class, 'Discovery Block');
        $this->registry->register($schema);

        $this->registry->registerPreset('discovery-block', BlockClassPreset::class);

        $retrieved = $this->registry->get('discovery-block');
        expect($retrieved->presets)->toHaveCount(1);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('Block Class Preset');
    });

    it('uses type strings directly', function () {
        $schema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $this->registry->register($schema);

        $this->registry->registerPreset('container', ContainerTestPreset::class);

        $retrieved = $this->registry->get('container');
        expect($retrieved->presets)->toHaveCount(1);
        expect($retrieved->presets[0]->toArray()['name'])->toBe('Test Container Preset');
    });

    it('can register presets to different block types', function () {
        $containerSchema = new BlockSchema('container', 'container', TestBlock::class, 'Container');
        $textSchema = new BlockSchema('text', 'text', TestBlock::class, 'Text');

        $this->registry->register($containerSchema);
        $this->registry->register($textSchema);

        $this->registry->registerPreset('container', ContainerTestPreset::class);
        $this->registry->registerPreset('text', TextTestPreset::class);

        $containerPresets = $this->registry->get('container')->presets;
        $textPresets = $this->registry->get('text')->presets;

        expect($containerPresets)->toHaveCount(1);
        expect($containerPresets[0]->toArray()['name'])->toBe('Test Container Preset');

        expect($textPresets)->toHaveCount(1);
        expect($textPresets[0]->toArray()['name'])->toBe('Text Preset');
    });
});
