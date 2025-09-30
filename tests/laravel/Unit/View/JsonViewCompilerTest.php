<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\BlockSchemaRegistry;
use Craftile\Laravel\View\BlockCacheManager;
use Craftile\Laravel\View\JsonViewCompiler;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->cachePath = sys_get_temp_dir().'/craftile_test_cache';
    $this->bladeCompiler = app(BladeCompiler::class);
    $this->cacheManager = new BlockCacheManager($this->files);

    $this->compiler = new JsonViewCompiler(
        $this->files,
        $this->cachePath,
        $this->bladeCompiler,
        $this->cacheManager
    );

    // Register test block schema
    $registry = app(BlockSchemaRegistry::class);
    $registry->register(new BlockSchema('test', 'TestClass', 'Test Block'));

    // Ensure cache directory exists
    if (! $this->files->isDirectory($this->cachePath)) {
        $this->files->makeDirectory($this->cachePath, 0755, true);
    }
});

afterEach(function () {
    // Clean up cache directory
    if ($this->files->isDirectory($this->cachePath)) {
        $this->files->deleteDirectory($this->cachePath);
    }
});

test('can compile simple template data', function () {
    $templateData = [
        'blocks' => [
            'header' => [
                'id' => 'header',
                'type' => 'test',
                'properties' => ['title' => 'Test Title'],
            ],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    expect($compiled)->toContain('BlockDatastore::getBlock');
    expect($compiled)->toContain('craftile');
    expect($compiled)->toContain('startRegion');
    expect($compiled)->toContain('endRegion');
});

test('compiles file and caches result', function () {
    $filePath = sys_get_temp_dir().'/test_template.json';
    $template = json_encode([
        'blocks' => [
            'test' => [
                'id' => 'test',
                'type' => 'test',
                'properties' => ['content' => 'Test Content'],
            ],
        ],
        'regions' => [
            ['name' => 'content', 'blocks' => ['test']],
        ],
    ]);

    $this->files->put($filePath, $template);

    $this->compiler->compile($filePath);
    $compiledPath = $this->compiler->getCompiledPath($filePath);

    expect($this->files->exists($compiledPath))->toBeTrue();

    $compiled = $this->files->get($compiledPath);
    expect($compiled)->toContain('BlockDatastore::loadFile');
    expect($compiled)->toContain('getBlock');

    // Clean up
    $this->files->delete($filePath);
});

test('generates proper PHP code structure', function () {
    $templateData = [
        'blocks' => [
            'header' => [
                'id' => 'header',
                'type' => 'test',
                'properties' => ['title' => 'Test'],
            ],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['header']],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    // Should contain PHP opening tag
    expect($compiled)->toContain('<?php');

    // Should contain block rendering code
    expect($compiled)->toContain('BlockDatastore::getBlock');
    expect($compiled)->toContain('$__blockData');

    // Should contain Craftile helper calls
    expect($compiled)->toContain('craftile()->startRegion');
    expect($compiled)->toContain('craftile()->endRegion');
});

test('handles empty template data', function () {
    $templateData = [];

    $compiled = $this->compiler->compileTemplate($templateData);

    expect($compiled)->toBeString();
    expect($compiled)->toContain('// Empty template');
});

test('handles template with no regions (now auto-creates main region)', function () {
    $templateData = [
        'blocks' => [
            'test' => ['id' => 'test', 'type' => 'test'],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    expect($compiled)->toContain('BlockDatastore::getBlock');
    expect($compiled)->toContain('startRegion');
    expect($compiled)->toContain('"main"'); // Auto-created region
});

test('handles template with empty regions', function () {
    $templateData = [
        'blocks' => [],
        'regions' => [
            ['name' => 'empty', 'blocks' => []],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    expect($compiled)->toContain('startRegion');
    expect($compiled)->toContain('endRegion');
});

test('handles blocks with properties', function () {
    $templateData = [
        'blocks' => [
            'content' => [
                'id' => 'content',
                'type' => 'test',
                'properties' => [
                    'title' => 'Test Title',
                    'content' => 'Test Content',
                    'visible' => true,
                ],
            ],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['content']],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    expect($compiled)->toContain('BlockDatastore::getBlock');
    expect($compiled)->toContain('$__blockData');
});

test('handles order-only format', function () {
    $templateData = [
        'blocks' => [
            'header' => [
                'id' => 'header',
                'type' => 'test',
                'properties' => ['content' => 'Header'],
            ],
            'footer' => [
                'id' => 'footer',
                'type' => 'test',
                'properties' => ['content' => 'Footer'],
            ],
        ],
        'order' => ['header', 'footer'],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    expect($compiled)->toContain('BlockDatastore::getBlock');
    expect($compiled)->toContain('startRegion');
    expect($compiled)->toContain('endRegion');
    expect($compiled)->toContain('"main"'); // Default region name
});

test('handles blocks-only format with auto-computed order', function () {
    $templateData = [
        'blocks' => [
            'header' => [
                'id' => 'header',
                'type' => 'test',
                'properties' => ['content' => 'Header'],
            ],
            'description' => [
                'id' => 'description',
                'type' => 'test',
                'properties' => ['content' => 'Description'],
            ],
        ],
        // No order or regions - should auto-compute from block keys
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    expect($compiled)->toContain('BlockDatastore::getBlock');
    expect($compiled)->toContain('startRegion');
    expect($compiled)->toContain('endRegion');
    expect($compiled)->toContain('"main"'); // Default region name
    expect($compiled)->toContain('"header"'); // Should include header block
    expect($compiled)->toContain('"description"'); // Should include description block
});

test('invalidates parent cache when child block changes', function () {
    $cacheManager = $this->app[BlockCacheManager::class];

    // Create initial template with original content
    $template = [
        'blocks' => [
            'header' => ['id' => 'header', 'type' => 'test', 'properties' => ['content' => 'Header'], 'children' => ['nav']],
            'nav' => ['id' => 'nav', 'type' => 'test', 'properties' => ['content' => 'Nav'], 'parentId' => 'header', 'children' => ['menu']],
            'menu' => ['id' => 'menu', 'type' => 'test', 'properties' => ['content' => 'Menu'], 'parentId' => 'nav', 'children' => ['item1']],
            'item1' => ['id' => 'item1', 'type' => 'test', 'properties' => ['content' => 'Original Item'], 'parentId' => 'menu'],
        ],
    ];

    // Cache all original blocks
    foreach ($template['blocks'] as $blockData) {
        $hash = $cacheManager->getCacheKey($blockData);
        $cacheManager->put($hash, 'cached content');
        expect($cacheManager->exists($hash))->toBeTrue();
    }

    // Store original cache keys
    $originalHeaderKey = $cacheManager->getCacheKey($template['blocks']['header']);
    $originalNavKey = $cacheManager->getCacheKey($template['blocks']['nav']);
    $originalMenuKey = $cacheManager->getCacheKey($template['blocks']['menu']);
    $originalItem1Key = $cacheManager->getCacheKey($template['blocks']['item1']);

    // Now change item1 content - this creates a new cache key naturally
    $template['blocks']['item1']['properties']['content'] = 'Updated Item';
    $newItem1Key = $cacheManager->getCacheKey($template['blocks']['item1']);

    // Verify the cache key actually changed
    expect($newItem1Key)->not->toBe($originalItem1Key);
    // New cache key has no cache (this makes it a "changed block")
    expect($cacheManager->exists($newItem1Key))->toBeFalse();

    // Use reflection to test invalidation directly
    $reflection = new ReflectionClass($this->compiler);
    $invalidateStaleBlockCaches = $reflection->getMethod('invalidateStaleBlockCaches');
    $invalidateStaleBlockCaches->setAccessible(true);

    // This should detect item1 as changed (new hash, no cache) and invalidate ancestors
    $invalidateStaleBlockCaches->invoke($this->compiler, $template);

    // Check that ancestors were invalidated (original caches should be gone)
    expect($cacheManager->exists($originalHeaderKey))->toBeFalse();
    expect($cacheManager->exists($originalNavKey))->toBeFalse();
    expect($cacheManager->exists($originalMenuKey))->toBeFalse();
    expect($cacheManager->exists($originalItem1Key))->toBeFalse(); // Original cache flushed (changed block gets all versions deleted)
    expect($cacheManager->exists($newItem1Key))->toBeFalse(); // New item1 has no cache yet
});

test('finds changed blocks correctly', function () {
    $compiler = new JsonViewCompiler(
        $this->app['files'],
        $this->app['config']['view.compiled'],
        $this->app['blade.compiler'],
        $this->app[BlockCacheManager::class]
    );

    $template = [
        'blocks' => [
            'block1' => ['id' => 'block1', 'type' => 'test', 'properties' => ['content' => 'Block 1']],
            'block2' => ['id' => 'block2', 'type' => 'test', 'properties' => ['content' => 'Block 2']],
        ],
    ];

    // Use reflection to access protected method
    $reflection = new ReflectionClass($compiler);
    $findChangedBlocks = $reflection->getMethod('findChangedBlocks');
    $findChangedBlocks->setAccessible(true);

    // Initially, no blocks are cached, so both should be "changed"
    $changedBlocks = $findChangedBlocks->invoke($compiler, $template);

    expect($changedBlocks)->toContain('block1');
    expect($changedBlocks)->toContain('block2');
    expect($changedBlocks)->toHaveCount(2);
});

test('finds ancestors correctly using parent chain', function () {
    $compiler = new JsonViewCompiler(
        $this->app['files'],
        $this->app['config']['view.compiled'],
        $this->app['blade.compiler'],
        $this->app[BlockCacheManager::class]
    );

    $template = [
        'blocks' => [
            'grandparent' => ['id' => 'grandparent', 'type' => 'test'],
            'parent' => ['id' => 'parent', 'type' => 'test', 'parentId' => 'grandparent'],
            'child' => ['id' => 'child', 'type' => 'test', 'parentId' => 'parent'],
        ],
    ];

    // Use reflection to access protected methods
    $reflection = new ReflectionClass($compiler);
    $findBlocksAndAncestors = $reflection->getMethod('findBlocksAndAncestors');
    $findBlocksAndAncestors->setAccessible(true);

    $blocksToInvalidate = $findBlocksAndAncestors->invoke($compiler, ['child'], $template);

    expect($blocksToInvalidate)->toContain('child');      // The changed block itself
    expect($blocksToInvalidate)->toContain('parent');     // Immediate parent
    expect($blocksToInvalidate)->toContain('grandparent'); // Grandparent
    expect($blocksToInvalidate)->toHaveCount(3);
});
