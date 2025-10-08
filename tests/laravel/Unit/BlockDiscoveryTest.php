<?php

declare(strict_types=1);

use Craftile\Laravel\BlockDiscovery;
use Craftile\Laravel\BlockSchemaRegistry;

describe('BlockDiscovery', function () {
    beforeEach(function () {
        $this->registry = new BlockSchemaRegistry;
        $this->discovery = new BlockDiscovery($this->registry);
    });

    it('discovers and registers block classes', function () {
        $stubsDir = __DIR__.'/../Stubs/Discovery';

        $this->discovery->scan('Tests\Laravel\Stubs\Discovery', $stubsDir);

        // Should find all block classes (including subdirectory)
        expect($this->registry->hasSchema('stub-test-block'))->toBeTrue();
        expect($this->registry->hasSchema('discovery-block'))->toBeTrue();
        expect($this->registry->hasSchema('text-block'))->toBeTrue();
        expect($this->registry->hasSchema('container'))->toBeTrue();

        expect($this->registry->getAllSchemas())->toHaveCount(4);
    });

    it('handles missing directories gracefully', function () {
        $nonExistentDir = __DIR__.'/../../Stubs/NonExistent';

        $this->discovery->scan('Tests\Stubs\NonExistent', $nonExistentDir);

        expect($this->registry->getAllSchemas())->toBeEmpty();
    });

    it('uses custom BlockSchema class from config', function () {
        config(['craftile.block_schema_class' => CustomBlockSchema::class]);

        $stubsDir = __DIR__.'/../Stubs/Discovery';
        $this->discovery->scan('Tests\Laravel\Stubs\Discovery', $stubsDir);

        $schema = $this->registry->getSchema('stub-test-block');

        expect($schema)->toBeInstanceOf(CustomBlockSchema::class);
        expect($schema->customProperty)->toBe('custom value');
    });

    it('throws exception when custom BlockSchema class does not extend BlockSchema', function () {
        config(['craftile.block_schema_class' => \stdClass::class]);

        $stubsDir = __DIR__.'/../Stubs/Discovery';

        expect(fn () => $this->discovery->scan('Tests\Laravel\Stubs\Discovery', $stubsDir))
            ->toThrow(\InvalidArgumentException::class, 'must extend Craftile\Core\Data\BlockSchema');
    });
});

// Custom BlockSchema class for testing
class CustomBlockSchema extends \Craftile\Core\Data\BlockSchema
{
    public string $customProperty = 'custom value';

    public static function fromClass(string $blockClass): static
    {
        $instance = parent::fromClass($blockClass);
        $instance->customProperty = 'custom value';

        return $instance;
    }
}
