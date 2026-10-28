<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\BlockData;
use Craftile\Laravel\BlockDatastore;
use Craftile\Laravel\BlockSchemaRegistry;
use Craftile\Laravel\Facades\Craftile;
use Craftile\Laravel\PreviewDataCollector;
use Craftile\Laravel\View\JsonViewCompiler;
use Craftile\Laravel\View\JsonViewParser;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;

/**
 * Long-lived workers (Octane, queue) boot the application once and serve many requests from it.
 * Between requests they forget scoped instances, and Octane binds the next request.
 */
class WorkerLeafBlock
{
    protected BlockData $block;

    public function setBlockData(BlockData $block): void
    {
        $this->block = $block;
    }

    public function render(): string
    {
        return "<span>{$this->block->id}</span>";
    }
}

beforeEach(function () {
    $this->files = new Filesystem;
    $this->dir = sys_get_temp_dir().'/craftile-worker-'.uniqid();
    $this->files->makeDirectory($this->dir.'/compiled', 0755, true);

    config(['view.compiled' => $this->dir.'/compiled']);

    app(BlockSchemaRegistry::class)->register(new BlockSchema('worker-leaf', 'worker-leaf', WorkerLeafBlock::class, 'Leaf'));

    $this->writeTemplate = function (string $name, array $blockIds): string {
        $path = "{$this->dir}/{$name}.json";
        $blocks = [];
        foreach ($blockIds as $id) {
            $blocks[$id] = ['id' => $id, 'type' => 'worker-leaf'];
        }

        $this->files->put($path, json_encode([
            'blocks' => $blocks,
            'regions' => [['name' => 'main', 'blocks' => $blockIds]],
        ]));

        return $path;
    };

    $this->nextRequest = function (array $query = []): void {
        app()->forgetScopedInstances();
        app()->instance('request', Request::create('/', 'GET', $query));
    };
});

afterEach(function () {
    $this->files->deleteDirectory($this->dir);
});

test('blocks loaded in one request are absent in the next', function () {
    $product = ($this->writeTemplate)('product', ['gallery', 'breadcrumbs']);
    $home = ($this->writeTemplate)('home', ['hero']);

    view()->file($product)->render();
    expect(app(BlockDatastore::class)->hasBlock('gallery'))->toBeTrue();

    ($this->nextRequest)();

    expect(view()->file($home)->render())->toContain('<span>hero</span>');
    expect(array_keys(app(BlockDatastore::class)->getAllBlocks()))->toBe(['hero']);
    expect(Craftile::resolveStaticBlockId('gallery', 'gallery'))->toBeNull();
});

test('a template edited between requests is parsed fresh', function () {
    $path = ($this->writeTemplate)('home', ['hero', 'intro']);

    expect(array_keys(app(JsonViewParser::class)->parse($path)['blocks']))->toBe(['hero', 'intro']);

    // Octane terminates the request before it forgets scoped instances.
    app()->terminate();
    ($this->nextRequest)();

    // A publish rewrites the template: intro removed, banner added.
    ($this->writeTemplate)('home', ['hero', 'banner']);
    touch($path, time() + 10);

    expect(array_keys(app(JsonViewParser::class)->parse($path)['blocks']))->toBe(['hero', 'banner']);
    expect(app(JsonViewCompiler::class)->compileJsonFile($path))
        ->toContain('getBlock("banner")')
        ->not->toContain('getBlock("intro")');
});

test('a rendered template published between requests is compiled again', function () {
    $path = ($this->writeTemplate)('home', ['hero', 'intro']);

    expect(view()->file($path)->render())->toContain('<span>intro</span>');

    // Octane terminates the request before it forgets scoped instances.
    app()->terminate();
    ($this->nextRequest)();

    ($this->writeTemplate)('home', ['hero', 'banner']);
    touch($path, time() + 10);

    expect(view()->file($path)->render())
        ->toContain('<span>banner</span>')
        ->not->toContain('<span>intro</span>');
});

test('inPreview answers per request', function () {
    ($this->nextRequest)(['_preview' => '1']);
    expect(Craftile::inPreview())->toBeTrue();

    ($this->nextRequest)();
    expect(Craftile::inPreview())->toBeFalse();

    ($this->nextRequest)(['_preview' => '1']);
    expect(Craftile::inPreview())->toBeTrue();
});

test('inPreview answers per request with a detector registered at boot', function () {
    Craftile::detectPreviewUsing(fn () => request()->has('_design'));

    ($this->nextRequest)();
    expect(Craftile::inPreview())->toBeFalse();

    ($this->nextRequest)(['_design' => '1']);
    expect(Craftile::inPreview())->toBeTrue();

    ($this->nextRequest)();
    expect(Craftile::inPreview())->toBeFalse();
});

test('the preview data collector starts empty in the next request', function () {
    $product = ($this->writeTemplate)('product', ['gallery']);
    $home = ($this->writeTemplate)('home', ['hero']);

    ($this->nextRequest)(['_preview' => '1']);
    view()->file($product)->render();
    expect(array_keys(app(PreviewDataCollector::class)->getCollectedData()['blocks']))->toBe(['gallery']);

    ($this->nextRequest)(['_preview' => '1']);
    expect(app(PreviewDataCollector::class)->getCollectedData())->toBe(['blocks' => [], 'regions' => []]);

    view()->file($home)->render();
    expect(array_keys(app(PreviewDataCollector::class)->getCollectedData()['blocks']))->toBe(['hero']);
});

test('a template is parsed once per request', function () {
    $path = ($this->writeTemplate)('home', ['hero']);

    $parses = 0;
    Craftile::normalizeTemplateUsing(function (array $data) use (&$parses) {
        $parses++;

        return $data;
    });
    Craftile::detectPreviewUsing(fn () => true);

    app(JsonViewParser::class)->parse($path);
    app(JsonViewParser::class)->parse($path);
    app(BlockDatastore::class)->loadFile($path);

    expect($parses)->toBe(1);
});

test('inPreview is computed once per request', function () {
    $calls = 0;
    Craftile::detectPreviewUsing(function () use (&$calls) {
        $calls++;

        return true;
    });

    Craftile::inPreview();
    Craftile::inPreview();

    expect($calls)->toBe(1);
});
