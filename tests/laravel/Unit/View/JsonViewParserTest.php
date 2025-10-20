<?php

use Craftile\Laravel\Facades\Craftile;
use Craftile\Laravel\View\JsonViewParser;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->testDir = sys_get_temp_dir().'/craftile-parser-test-'.uniqid();
    mkdir($this->testDir);

    $this->parser = new JsonViewParser;
});

afterEach(function () {
    if (is_dir($this->testDir)) {
        array_map('unlink', glob("{$this->testDir}/*.*"));
        rmdir($this->testDir);
    }

    $this->parser->clearCache();
});

describe('Basic Parsing', function () {
    test('parses JSON files correctly', function () {
        $templateData = [
            'blocks' => [
                'hero' => [
                    'id' => 'hero',
                    'type' => 'hero',
                    'properties' => ['title' => 'Welcome'],
                    'children' => [],
                ],
            ],
        ];

        $filePath = $this->testDir.'/template.json';
        file_put_contents($filePath, json_encode($templateData));

        $result = $this->parser->parse($filePath);

        expect($result)->toBe($templateData);
        expect($result['blocks']['hero']['properties']['title'])->toBe('Welcome');
    });

    test('parses YAML files correctly', function () {
        $yamlContent = <<<'YAML'
blocks:
  hero:
    id: hero
    type: hero
    properties:
      title: "Welcome from YAML"
    children: []
YAML;

        $filePath = $this->testDir.'/template.yaml';
        file_put_contents($filePath, $yamlContent);

        $result = $this->parser->parse($filePath);

        expect($result)->toBeArray();
        expect($result['blocks']['hero']['properties']['title'])->toBe('Welcome from YAML');
    });

    test('parses .yml extension', function () {
        $yamlContent = <<<'YAML'
blocks:
  footer:
    id: footer
    type: footer
    properties: {}
    children: []
YAML;

        $filePath = $this->testDir.'/template.yml';
        file_put_contents($filePath, $yamlContent);

        $result = $this->parser->parse($filePath);

        expect($result)->toBeArray();
        expect($result['blocks']['footer']['id'])->toBe('footer');
    });

    test('parses PHP template files (.craft.php)', function () {
        $phpContent = <<<'PHP'
<?php
return [
    'blocks' => [
        'hero' => [
            'id' => 'hero',
            'type' => 'hero',
            'properties' => ['title' => 'PHP Template'],
            'children' => [],
        ],
    ],
];
PHP;

        $filePath = $this->testDir.'/template.craft.php';
        file_put_contents($filePath, $phpContent);

        $result = $this->parser->parse($filePath);

        expect($result)->toBeArray();
        expect($result['blocks']['hero']['properties']['title'])->toBe('PHP Template');
    });

    test('parses PHP template returning Template instance', function () {
        $phpContent = <<<'PHP'
<?php
use Craftile\Core\Data\Template;

return Template::make()
    ->block('hero', 'hero', fn($b) => $b->properties(['title' => 'Template Instance']))
    ->toArray();
PHP;

        $filePath = $this->testDir.'/template.craft.php';
        file_put_contents($filePath, $phpContent);

        $result = $this->parser->parse($filePath);

        expect($result)->toBeArray();
        expect($result['blocks']['hero']['properties']['title'])->toBe('Template Instance');
    });
});

describe('PHP Template Extensions', function () {
    test('detects .craft.php as PHP template (default config)', function () {
        config(['craftile.php_template_extensions' => ['craft.php']]);

        $phpContent = <<<'PHP'
<?php
return ['blocks' => []];
PHP;

        $filePath = $this->testDir.'/test.craft.php';
        file_put_contents($filePath, $phpContent);

        $parser = new JsonViewParser;
        $result = $parser->parse($filePath);

        expect($result)->toBeArray();
        expect($result['blocks'])->toBe([]);
    });

    test('supports custom PHP template extensions from config', function () {
        config(['craftile.php_template_extensions' => ['template.php']]);

        $phpContent = <<<'PHP'
<?php
return ['blocks' => ['test' => ['id' => 'test', 'type' => 'test']]];
PHP;

        $filePath = $this->testDir.'/custom.template.php';
        file_put_contents($filePath, $phpContent);

        $parser = new JsonViewParser;
        $result = $parser->parse($filePath);

        expect($result)->toBeArray();
        expect($result['blocks']['test']['id'])->toBe('test');
    });

    test('handles multiple custom PHP template extensions', function () {
        config(['craftile.php_template_extensions' => ['craft.php', 'template.php', 'tpl.php']]);

        $phpContent = <<<'PHP'
<?php
return ['blocks' => []];
PHP;

        // Test first extension
        $file1 = $this->testDir.'/test1.craft.php';
        file_put_contents($file1, $phpContent);

        // Test second extension
        $file2 = $this->testDir.'/test2.template.php';
        file_put_contents($file2, $phpContent);

        // Test third extension
        $file3 = $this->testDir.'/test3.tpl.php';
        file_put_contents($file3, $phpContent);

        $parser = new JsonViewParser;

        expect($parser->parse($file1))->toBeArray();
        expect($parser->parse($file2))->toBeArray();
        expect($parser->parse($file3))->toBeArray();
    });
});

describe('Caching', function () {
    test('uses in-memory cache in preview mode', function () {
        $templateData = ['blocks' => ['test' => ['id' => 'test', 'type' => 'test']]];
        $filePath = $this->testDir.'/template.json';
        file_put_contents($filePath, json_encode($templateData));

        Craftile::shouldReceive('inPreview')->andReturn(true);

        // Cache facade should never be called in preview mode
        Cache::shouldReceive('remember')->never();

        // First call - parses file
        $result1 = $this->parser->parse($filePath);
        expect($result1)->toBe($templateData);

        // Second call - uses in-memory cache
        $result2 = $this->parser->parse($filePath);
        expect($result2)->toBe($templateData);

        // Both results should be identical
        expect($result1)->toBe($result2);
    });

    test('uses Laravel cache in production mode', function () {
        $templateData = ['blocks' => ['test' => ['id' => 'test', 'type' => 'test']]];
        $filePath = $this->testDir.'/template.json';
        file_put_contents($filePath, json_encode($templateData));

        Craftile::shouldReceive('inPreview')->andReturn(false);

        // Mock Cache::remember to verify it's called
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) use ($filePath) {
                // Verify cache key format
                expect($key)->toStartWith('craftile_template_');
                expect($key)->toContain(md5($filePath.'_'.filemtime($filePath)));

                return true;
            })
            ->andReturn($templateData);

        $result = $this->parser->parse($filePath);

        expect($result)->toBe($templateData);
    });

    test('uses configurable cache TTL', function () {
        $templateData = ['blocks' => []];
        $filePath = $this->testDir.'/template.json';
        file_put_contents($filePath, json_encode($templateData));

        config(['craftile.cache.ttl' => 7200]);

        Craftile::shouldReceive('inPreview')->andReturn(false);

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) {
                expect($ttl)->toBe(7200);

                return true;
            })
            ->andReturn($templateData);

        $parser = new JsonViewParser;
        $parser->parse($filePath);
    });

    test('defaults to 3600s TTL when not configured', function () {
        $templateData = ['blocks' => []];
        $filePath = $this->testDir.'/template.json';
        file_put_contents($filePath, json_encode($templateData));

        config(['craftile.cache' => []]);

        Craftile::shouldReceive('inPreview')->andReturn(false);

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) {
                expect($ttl)->toBe(3600);

                return true;
            })
            ->andReturn($templateData);

        $parser = new JsonViewParser;
        $parser->parse($filePath);
    });

    test('cache key includes file path and modification time', function () {
        $templateData = ['blocks' => ['test' => ['id' => 'test']]];
        $filePath = $this->testDir.'/template.json';
        file_put_contents($filePath, json_encode($templateData));

        Craftile::shouldReceive('inPreview')->andReturn(false);

        $mtime = filemtime($filePath);
        $expectedKey = 'craftile_template_'.md5($filePath.'_'.$mtime);

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) use ($expectedKey, $filePath, $mtime) {
                // Verify cache key format includes path and mtime
                expect($key)->toBe($expectedKey);
                expect($key)->toStartWith('craftile_template_');
                expect($key)->toContain(md5($filePath.'_'.$mtime));

                return true;
            })
            ->andReturn($templateData);

        $parser = new JsonViewParser;
        $parser->parse($filePath);
    });

    test('clearCache() clears in-memory cache', function () {
        $templateData = ['blocks' => ['test' => ['id' => 'test']]];
        $filePath = $this->testDir.'/template.json';
        file_put_contents($filePath, json_encode($templateData));

        Craftile::shouldReceive('inPreview')->andReturn(true);

        // First parse - should read file
        $result1 = $this->parser->parse($filePath);
        expect($result1)->toBe($templateData);

        // Modify file
        $newData = ['blocks' => ['new' => ['id' => 'new']]];
        file_put_contents($filePath, json_encode($newData));

        // Second parse - should use cached data
        $result2 = $this->parser->parse($filePath);
        expect($result2)->toBe($templateData); // Still old data

        // Clear cache
        $this->parser->clearCache();

        // Third parse - should read file again
        $result3 = $this->parser->parse($filePath);
        expect($result3)->toBe($newData); // New data
    });
});
