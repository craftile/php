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
});
