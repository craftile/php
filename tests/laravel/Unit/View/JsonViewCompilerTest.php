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

    $compiled = $this->compiler->compileTemplateData($templateData);

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

test('uses cached compilation when file unchanged', function () {
    $filePath = sys_get_temp_dir().'/test_cache_template.json';
    $template = json_encode([
        'blocks' => ['test' => ['id' => 'test', 'type' => 'test']],
        'regions' => [['name' => 'main', 'blocks' => ['test']]],
    ]);

    $this->files->put($filePath, $template);

    // First compilation
    $this->compiler->compile($filePath);
    $compiledPath = $this->compiler->getCompiledPath($filePath);
    $firstTime = $this->files->lastModified($compiledPath);

    // Wait a moment to ensure different timestamps
    usleep(100000); // 100ms

    // Second compilation (should use cache)
    $this->compiler->compile($filePath);
    $secondTime = $this->files->lastModified($compiledPath);

    expect($firstTime)->toBe($secondTime);

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

    $compiled = $this->compiler->compileTemplateData($templateData);

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

    $compiled = $this->compiler->compileTemplateData($templateData);

    expect($compiled)->toBeString();
    expect($compiled)->toContain('// Empty template');
});

test('handles template with no regions (now auto-creates main region)', function () {
    $templateData = [
        'blocks' => [
            'test' => ['id' => 'test', 'type' => 'test'],
        ],
    ];

    $compiled = $this->compiler->compileTemplateData($templateData);

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

    $compiled = $this->compiler->compileTemplateData($templateData);

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

    $compiled = $this->compiler->compileTemplateData($templateData);

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

    $compiled = $this->compiler->compileTemplateData($templateData);

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

    $compiled = $this->compiler->compileTemplateData($templateData);

    expect($compiled)->toContain('BlockDatastore::getBlock');
    expect($compiled)->toContain('startRegion');
    expect($compiled)->toContain('endRegion');
    expect($compiled)->toContain('"main"'); // Default region name
    expect($compiled)->toContain('"header"'); // Should include header block
    expect($compiled)->toContain('"description"'); // Should include description block
});
