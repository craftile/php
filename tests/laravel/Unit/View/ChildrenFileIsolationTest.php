<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\BlockData;
use Craftile\Laravel\BlockDatastore;
use Craftile\Laravel\BlockSchemaRegistry;
use Craftile\Laravel\Facades\BlockDatastore as BlockDatastoreFacade;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;

/**
 * Templates that share block IDs (a live template and its editor draft, or per-locale copies)
 * must not share compiled children files.
 */
class IsolationGroupBlock
{
    public static string $viewPath = '';

    public function render()
    {
        return view()->file(self::$viewPath);
    }
}

class IsolationSectionBlock
{
    public static string $viewPath = '';

    public function render()
    {
        return view()->file(self::$viewPath);
    }
}

class IsolationLeafBlock
{
    protected BlockData $block;

    public function setBlockData(BlockData $block): void
    {
        $this->block = $block;
    }

    public function render(): string
    {
        return "<span class=\"{$this->block->type}\">{$this->block->id}</span>";
    }
}

class IsolationOtherLeafBlock extends IsolationLeafBlock
{
    public function render(): string
    {
        return "<em>{$this->block->id}</em>";
    }
}

beforeEach(function () {
    $this->files = new Filesystem;
    $this->dir = sys_get_temp_dir().'/craftile-isolation-'.uniqid();
    $this->files->makeDirectory($this->dir.'/compiled', 0755, true);

    config(['view.compiled' => $this->dir.'/compiled']);

    // Static block code calls the facade alias that the package registers through auto-discovery.
    AliasLoader::getInstance()->alias('BlockDatastore', BlockDatastoreFacade::class);

    IsolationGroupBlock::$viewPath = $this->dir.'/group.blade.php';
    $this->files->put(IsolationGroupBlock::$viewPath, '<div class="group">@children</div>');

    IsolationSectionBlock::$viewPath = $this->dir.'/section.blade.php';
    $this->files->put(IsolationSectionBlock::$viewPath, "<section>@craftileBlock('group', 'hero')</section>");

    $registry = app(BlockSchemaRegistry::class);
    $registry->register(new BlockSchema('group', 'group', IsolationGroupBlock::class, 'Group'));
    $registry->register(new BlockSchema('section', 'section', IsolationSectionBlock::class, 'Section'));
    $registry->register(new BlockSchema('leaf', 'leaf', IsolationLeafBlock::class, 'Leaf'));
    $registry->register(new BlockSchema('other-leaf', 'other-leaf', IsolationOtherLeafBlock::class, 'Other leaf'));

    // Each render simulates a separate request: only the rendered template's blocks are loaded.
    $this->render = function (string $path): string {
        app(BlockDatastore::class)->clear();

        return view()->file($path)->render();
    };

    $this->writeTemplate = function (string $name, array $blocks, array $regionBlocks = ['group']): string {
        $path = "{$this->dir}/{$name}.json";
        $this->files->put($path, json_encode([
            'blocks' => $blocks,
            'regions' => [['name' => 'main', 'blocks' => $regionBlocks]],
        ]));

        return $path;
    };

    // Only the listed children are defined, like a live template that has never seen a draft-only block.
    $this->groupTemplate = function (string $name, array $children): string {
        $blocks = ['group' => ['id' => 'group', 'type' => 'group', 'children' => $children]];
        foreach ($children as $id) {
            $blocks[$id] = ['id' => $id, 'type' => 'leaf', 'parentId' => 'group'];
        }

        return ($this->writeTemplate)($name, $blocks);
    };
});

afterEach(function () {
    app(BlockDatastore::class)->clear();
    $this->files->deleteDirectory($this->dir);
});

test('a child added in another template does not reach this template', function () {
    $live = ($this->groupTemplate)('live', ['x']);
    $draft = ($this->groupTemplate)('draft', ['x', 'z']);

    ($this->render)($live);
    expect(($this->render)($draft))->toContain('>z<');

    $html = ($this->render)($live);

    expect($html)->toContain('>x<');
    expect($html)->not->toContain('>z<');
});

test('a child removed in another template still renders in this template', function () {
    $live = ($this->groupTemplate)('live', ['x', 'y']);
    $draft = ($this->groupTemplate)('draft', ['x']);

    ($this->render)($live);
    expect(($this->render)($draft))->not->toContain('>y<');

    $html = ($this->render)($live);

    expect($html)->toContain('>x<');
    expect($html)->toContain('>y<');
});

test('children reordered in another template keep their order in this template', function () {
    $live = ($this->groupTemplate)('live', ['x', 'y']);
    $draft = ($this->groupTemplate)('draft', ['y', 'x']);

    ($this->render)($live);
    $draftHtml = ($this->render)($draft);
    expect(strpos($draftHtml, '>y<'))->toBeLessThan(strpos($draftHtml, '>x<'));

    $html = ($this->render)($live);

    expect(strpos($html, '>x<'))->toBeLessThan(strpos($html, '>y<'));
});

test('static block children are isolated between templates', function () {
    $template = function (string $name, array $heroChildren) {
        return ($this->writeTemplate)($name, [
            'section' => ['id' => 'section', 'type' => 'section', 'children' => ['hero']],
            'hero' => [
                'id' => 'hero',
                'type' => 'group',
                'static' => true,
                'semanticId' => 'hero',
                'parentId' => 'section',
                'children' => $heroChildren,
            ],
        ] + collect($heroChildren)->mapWithKeys(fn ($id) => [
            $id => ['id' => $id, 'type' => 'leaf', 'parentId' => 'hero'],
        ])->all(), ['section']);
    };

    $live = $template('live', ['x']);
    $draft = $template('draft', ['x', 'z']);

    ($this->render)($live);
    expect(($this->render)($draft))->toContain('>z<');

    $html = ($this->render)($live);

    expect($html)->toContain('>x<');
    expect($html)->not->toContain('>z<');
});

test('a cached parent still gets children for a changed child type', function () {
    // Without parentId on the children, a changed child does not invalidate the parent's cache.
    $blocks = fn (string $childType) => [
        'group' => ['id' => 'group', 'type' => 'group', 'children' => ['x']],
        'x' => ['id' => 'x', 'type' => $childType],
    ];

    $first = ($this->writeTemplate)('first', $blocks('leaf'));
    $second = ($this->writeTemplate)('second', $blocks('other-leaf'));

    expect(($this->render)($first))->toContain('<span class="leaf">x</span>');
    expect(($this->render)($second))->toContain('<em>x</em>');
});
