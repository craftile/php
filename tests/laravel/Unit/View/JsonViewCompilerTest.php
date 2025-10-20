<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\BlockSchemaRegistry;
use Craftile\Laravel\View\BlockCacheManager;
use Craftile\Laravel\View\JsonViewCompiler;
use Craftile\Laravel\View\JsonViewParser;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->cachePath = sys_get_temp_dir().'/craftile_test_cache';
    $this->bladeCompiler = app(BladeCompiler::class);
    $this->cacheManager = new BlockCacheManager($this->files);
    $this->parser = new JsonViewParser;

    $this->compiler = new JsonViewCompiler(
        $this->files,
        $this->cachePath,
        $this->bladeCompiler,
        $this->cacheManager,
        $this->parser
    );

    // Register test block schema
    $registry = app(BlockSchemaRegistry::class);
    $registry->register(new BlockSchema('test', 'test', 'TestClass', 'Test Block'));

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
        $this->app[BlockCacheManager::class],
        new JsonViewParser
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
        $this->app[BlockCacheManager::class],
        new JsonViewParser
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

test('skips rendering disabled blocks', function () {
    $templateData = [
        'blocks' => [
            'enabled' => [
                'id' => 'enabled',
                'type' => 'test',
                'properties' => ['content' => 'This should render'],
            ],
            'disabled' => [
                'id' => 'disabled',
                'type' => 'test',
                'properties' => ['content' => 'This should not render'],
                'disabled' => true,
            ],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['enabled', 'disabled']],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    // Should contain check for shouldRenderBlock
    expect($compiled)->toContain('craftile()->shouldRenderBlock');
    expect($compiled)->toContain('endif');

    // Should contain both block IDs in the compiled output
    expect($compiled)->toContain('enabled');
    expect($compiled)->toContain('disabled');
});

test('compiles block with wrapper when schema has wrapper property', function () {
    $registry = app(BlockSchemaRegistry::class);
    $registry->register(new BlockSchema(
        type: 'wrapped-block',
        slug: 'wrapped-block',
        class: 'WrappedBlock',
        name: 'Wrapped Block',
        wrapper: 'section#hero.container'
    ));

    $templateData = [
        'blocks' => [
            'hero' => [
                'id' => 'hero',
                'type' => 'wrapped-block',
                'properties' => ['title' => 'Hero Section'],
            ],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['hero']],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    // Should contain wrapper opening tag with data-block attribute
    expect($compiled)->toContain('<section data-block="hero" id="hero" class="container">');
    // Should contain wrapper closing tag
    expect($compiled)->toContain('</section>');
});

test('compiles block without wrapper when schema has no wrapper property', function () {
    $registry = app(BlockSchemaRegistry::class);
    $registry->register(new BlockSchema(
        type: 'no-wrapper',
        slug: 'no-wrapper',
        class: 'NoWrapperBlock',
        name: 'No Wrapper Block'
    ));

    $templateData = [
        'blocks' => [
            'content' => [
                'id' => 'content',
                'type' => 'no-wrapper',
                'properties' => ['text' => 'Content'],
            ],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['content']],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    // Should NOT contain wrapper tags
    expect($compiled)->not->toContain('data-block="content"');
});

test('compiles nested wrapper structure correctly', function () {
    $registry = app(BlockSchemaRegistry::class);
    $registry->register(new BlockSchema(
        type: 'nested-wrapper',
        slug: 'nested-wrapper',
        class: 'NestedWrapperBlock',
        name: 'Nested Wrapper Block',
        wrapper: 'section.outer>div.inner'
    ));

    $templateData = [
        'blocks' => [
            'nested' => [
                'id' => 'nested',
                'type' => 'nested-wrapper',
                'properties' => ['content' => 'Nested Content'],
            ],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['nested']],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    // Should contain opening wrapper with data-block on first tag
    expect($compiled)->toContain('<section data-block="nested" class="outer"><div class="inner">');
    // Should contain closing wrapper
    expect($compiled)->toContain('</div></section>');
});

test('wrapper auto-appends content placeholder when not present', function () {
    $registry = app(BlockSchemaRegistry::class);
    $registry->register(new BlockSchema(
        type: 'auto-content',
        slug: 'auto-content',
        class: 'AutoContentBlock',
        name: 'Auto Content Block',
        wrapper: 'div.wrapper'
    ));

    $templateData = [
        'blocks' => [
            'auto' => [
                'id' => 'auto',
                'type' => 'auto-content',
                'properties' => ['text' => 'Auto'],
            ],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['auto']],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    // Should have wrapper opening and closing
    expect($compiled)->toContain('<div data-block="auto" class="wrapper">');
    expect($compiled)->toContain('</div>');

    // Block content should be between wrapper tags
    expect($compiled)->toMatch('/<div data-block="auto" class="wrapper">.*<\/div>/s');
});

test('multiple blocks with different wrappers compile correctly', function () {
    $registry = app(BlockSchemaRegistry::class);
    $registry->register(new BlockSchema(
        type: 'wrapper-a',
        slug: 'wrapper-a',
        class: 'WrapperABlock',
        name: 'Wrapper A Block',
        wrapper: 'section#section-a.wrapper-a'
    ));
    $registry->register(new BlockSchema(
        type: 'wrapper-b',
        slug: 'wrapper-b',
        class: 'WrapperBBlock',
        name: 'Wrapper B Block',
        wrapper: 'article#article-b.wrapper-b'
    ));

    $templateData = [
        'blocks' => [
            'block-a' => [
                'id' => 'block-a',
                'type' => 'wrapper-a',
                'properties' => ['content' => 'A'],
            ],
            'block-b' => [
                'id' => 'block-b',
                'type' => 'wrapper-b',
                'properties' => ['content' => 'B'],
            ],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['block-a', 'block-b']],
        ],
    ];

    $compiled = $this->compiler->compileTemplate($templateData);

    // Should contain both wrappers with correct data-block attributes
    expect($compiled)->toContain('<section data-block="block-a" id="section-a" class="wrapper-a">');
    expect($compiled)->toContain('</section>');
    expect($compiled)->toContain('<article data-block="block-b" id="article-b" class="wrapper-b">');
    expect($compiled)->toContain('</article>');
});

test('assigns indices to blocks in regions', function () {
    // Create a temp file with blocks
    $filePath = sys_get_temp_dir().'/test_indices.json';
    $template = json_encode([
        'blocks' => [
            'block-1' => ['id' => 'block-1', 'type' => 'test', 'properties' => []],
            'block-2' => ['id' => 'block-2', 'type' => 'test', 'properties' => []],
            'block-3' => ['id' => 'block-3', 'type' => 'test', 'properties' => []],
        ],
        'regions' => [
            ['name' => 'main', 'blocks' => ['block-1', 'block-2', 'block-3']],
        ],
    ]);

    $this->files->put($filePath, $template);

    // Load the file through BlockDatastore
    app(\Craftile\Laravel\BlockDatastore::class)->loadFile($filePath);

    // Get blocks and check their indices
    $datastore = app(\Craftile\Laravel\BlockDatastore::class);

    $block1 = $datastore->getBlock('block-1');
    $block2 = $datastore->getBlock('block-2');
    $block3 = $datastore->getBlock('block-3');

    expect($block1->index)->toBe(0);
    expect($block1->iteration)->toBe(1);

    expect($block2->index)->toBe(1);
    expect($block2->iteration)->toBe(2);

    expect($block3->index)->toBe(2);
    expect($block3->iteration)->toBe(3);

    // Clean up
    $this->files->delete($filePath);
    $datastore->clear();
});
