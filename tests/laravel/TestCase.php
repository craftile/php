<?php

declare(strict_types=1);

namespace Tests\Laravel;

use Craftile\Laravel\CraftileServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CraftileServiceProvider::class,
        ];
    }

    /**
     * Mock the craftile() helper function for testing
     */
    protected function mockCraftileHelper(bool $inPreview = false, $blockSchema = null, bool $throwOnSchema = false): void
    {
        // For now, just note that this would require a proper mocking framework
        // In a real implementation, we'd mock the craftile() global helper
        $this->craftileInPreview = $inPreview;
        $this->craftileBlockSchema = $blockSchema;
        $this->craftileThrowOnSchema = $throwOnSchema;
    }
}
