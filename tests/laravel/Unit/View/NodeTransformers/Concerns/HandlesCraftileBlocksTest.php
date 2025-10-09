<?php

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\BlockSchemaRegistry;
use Craftile\Laravel\View\NodeTransformers\Concerns\HandlesCraftileBlocks;
use Craftile\Laravel\View\NodeTransformers\Concerns\HandlesErrors;
use Craftile\Laravel\View\NodeTransformers\Concerns\HandlesLiterals;
use Illuminate\View\Compilers\ComponentTagCompiler;
use Stillat\BladeParser\Nodes\DirectiveNode;

// Create a test class that uses the trait
class TestHandlesCraftileBlocks
{
    use HandlesCraftileBlocks, HandlesErrors, HandlesLiterals;

    // Make protected methods public for testing
    public function testCompileBlock(string $type, string $id, string $propertiesExpr, $node, $document): string
    {
        return $this->compileBlock($type, $id, $propertiesExpr, $node, $document);
    }

    public function setComponentCompilerPublic(?ComponentTagCompiler $compiler): void
    {
        $this->setComponentCompiler($compiler);
    }
}

beforeEach(function () {
    $this->handler = new TestHandlesCraftileBlocks;

    // Register test block schema
    app(BlockSchemaRegistry::class)->register(sampleBlockSchema());
});

test('compileBlock passes custom attributes to compiler', function () {
    $node = new DirectiveNode;
    $customAttrs = "['class' => 'custom-class', 'variant' => 'primary']";

    $compiled = $this->handler->testCompileBlock('text', 'test-id', $customAttrs, $node, null);

    // Verify custom attributes are in the compiled output
    expect($compiled)->toContain('craftile()->filterContext(get_defined_vars(), '.$customAttrs.')');
});

test('compileBlock uses empty array when no custom attributes provided', function () {
    $node = new DirectiveNode;

    $compiled = $this->handler->testCompileBlock('text', 'test-id', '[]', $node, null);

    // Should use empty array
    expect($compiled)->toContain('craftile()->filterContext(get_defined_vars(), [])');
});

test('compileBlock handles dynamic custom attributes expressions', function () {
    $node = new DirectiveNode;
    $customAttrs = '$customAttributes';

    $compiled = $this->handler->testCompileBlock('text', 'test-id', $customAttrs, $node, null);

    // Dynamic expression should be included
    expect($compiled)->toContain('craftile()->filterContext(get_defined_vars(), $customAttributes)');
});

test('compileBlock creates unique variable names with hash', function () {
    $node = new DirectiveNode;

    $compiled = $this->handler->testCompileBlock('text', 'unique-id-123', '[]', $node, null);

    $hash = hash('xxh128', 'unique-id-123');

    // Check that variables use the hash
    expect($compiled)->toContain('$__blockId'.$hash);
    expect($compiled)->toContain('$__blockParentId'.$hash);
    expect($compiled)->toContain('$__blockData'.$hash);
});

test('compileBlock includes block data creation with custom attributes context', function () {
    $node = new DirectiveNode;
    $customAttrs = "['variant' => 'hero']";

    $compiled = $this->handler->testCompileBlock('text', 'hero-1', $customAttrs, $node, null);

    // Verify block data is created
    expect($compiled)->toContain('BlockDatastore::getBlock');
    expect($compiled)->toContain('craftile()->createBlockData');

    // Verify context filtering includes custom attributes
    expect($compiled)->toContain("craftile()->filterContext(get_defined_vars(), ['variant' => 'hero'])");
});

test('compileBlock wraps content when schema has wrapper', function () {
    $schemaWithWrapper = new BlockSchema(
        type: 'wrapped',
        slug: 'wrapped',
        class: TestBlock::class,
        name: 'Wrapped Block',
        wrapper: 'div.wrapper'
    );

    app(BlockSchemaRegistry::class)->register($schemaWithWrapper);

    $node = new DirectiveNode;
    $compiled = $this->handler->testCompileBlock('wrapped', 'wrapped-1', '[]', $node, null);

    // Should include wrapper opening and closing
    expect($compiled)->toContain('<div data-block="wrapped-1" class="wrapper">');
    expect($compiled)->toContain('</div>');
});

test('compileBlock sets repeated flag when in loop', function () {
    $this->markTestSkipped('Requires full Document setup with loop context');
});

test('compileBlock includes preview mode checks', function () {
    $node = new DirectiveNode;

    $compiled = $this->handler->testCompileBlock('text', 'preview-test', '[]', $node, null);

    // Should include preview checks
    expect($compiled)->toContain('if (craftile()->inPreview())');
    expect($compiled)->toContain('craftile()->startBlock');
    expect($compiled)->toContain('craftile()->endBlock');
});

test('compileBlock includes shouldRenderBlock check', function () {
    $node = new DirectiveNode;

    $compiled = $this->handler->testCompileBlock('text', 'render-test', '[]', $node, null);

    // Should check if block should be rendered
    expect($compiled)->toContain('if (craftile()->shouldRenderBlock');
});

test('compileBlock cleans up block data variable', function () {
    $node = new DirectiveNode;

    $compiled = $this->handler->testCompileBlock('text', 'cleanup-test', '[]', $node, null);

    $hash = hash('xxh128', 'cleanup-test');

    // Should unset block data variable
    expect($compiled)->toContain('unset($__blockData'.$hash.');');
});

test('compileBlock uses component compiler when set', function () {
    $node = new DirectiveNode;

    // Mock component compiler
    $componentCompiler = Mockery::mock(ComponentTagCompiler::class);
    $componentCompiler->shouldReceive('compile')
        ->once()
        ->andReturn('compiled-with-component-compiler');

    $this->handler->setComponentCompilerPublic($componentCompiler);

    $compiled = $this->handler->testCompileBlock('text', 'component-test', '[]', $node, null);

    expect($compiled)->toBe('compiled-with-component-compiler');
});

test('compileBlock handles complex custom attributes with multiple properties', function () {
    $node = new DirectiveNode;
    $complexAttrs = "['class' => 'bg-blue', 'data-id' => \$item->id, 'style' => 'color: red', 'variant' => 'primary']";

    $compiled = $this->handler->testCompileBlock('text', 'complex-test', $complexAttrs, $node, null);

    // All attributes should be passed through
    expect($compiled)->toContain($complexAttrs);
});
