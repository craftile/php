<?php

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\View\Compilers\DefaultBlockCompiler;

test('supports any block schema', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'any-block',
        slug: 'any-block',
        class: 'AnyBlock',
        name: 'Any Block',
        description: 'Any block type',
        icon: 'block',
        category: 'test'
    );

    // Default compiler should support any schema (it's the fallback)
    expect($compiler->supports($schema))->toBeTrue();
});

test('compiles block with basic parameters', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'text-block',
        slug: 'text-block',
        class: TestBlock::class,
        name: 'Text Block'
    );
    $compiled = $compiler->compile($schema, 'abc123');

    expect($compiled)->toContain('$__blockInstanceabc123 = new \TestBlock;');
    expect($compiled)->toContain('if (method_exists($__blockInstanceabc123, \'setBlockData\'))');
    expect($compiled)->toContain('if (method_exists($__blockInstanceabc123, \'setContext\'))');
    expect($compiled)->toContain('$__blockViewabc123 = $__blockInstanceabc123->render();');
});

test('compiles block with custom attributes', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'button-block',
        slug: 'button-block',
        class: TestBlock::class,
        name: 'Button Block'
    );
    $customAttrs = "['variant' => 'primary', 'size' => 'large']";
    $compiled = $compiler->compile($schema, 'ghi789', '', $customAttrs);

    expect($compiled)->toContain('craftile()->filterContext(');
    expect($compiled)->toContain($customAttrs);
    expect($compiled)->toContain('$__contextghi789');
});

test('includes context filtering logic', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'test-block',
        slug: 'test-block',
        class: TestBlock::class,
        name: 'Test Block'
    );
    $compiled = $compiler->compile($schema, 'filter123');

    expect($compiled)->toContain('craftile()->filterContext(get_defined_vars()');
    expect($compiled)->toContain('$__contextfilter123');
});

test('handles view rendering and output', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'test-block',
        slug: 'test-block',
        class: TestBlock::class,
        name: 'Test Block'
    );
    $compiled = $compiler->compile($schema, 'render123');

    expect($compiled)->toContain('if($__blockViewrender123 instanceof \\Illuminate\\View\\View)');
    expect($compiled)->toContain('$__blockViewDatarender123 = array_merge(');
    expect($compiled)->toContain("['block' => \$__blockDatarender123, '__craftileContext' => \$__mergedContext]");
    expect($compiled)->toContain('echo $__blockViewrender123->with($__blockViewDatarender123)->render();');
    expect($compiled)->toContain('} else {');
    expect($compiled)->toContain('echo $__blockViewrender123;');
});

test('includes share() method support', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'test-block',
        slug: 'test-block',
        class: TestBlock::class,
        name: 'Test Block'
    );
    $compiled = $compiler->compile($schema, 'share456');

    expect($compiled)->toContain("method_exists(\$__blockInstanceshare456, 'share')");
    expect($compiled)->toContain('$__blockInstanceshare456->share()');
    expect($compiled)->toContain('$__sharedData');
    expect($compiled)->toContain('$__mergedContext');
    expect($compiled)->toContain('array_merge($__contextshare456, $__sharedData)');
});

test('includes variable cleanup', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'test-block',
        slug: 'test-block',
        class: TestBlock::class,
        name: 'Test Block'
    );
    $compiled = $compiler->compile($schema, 'cleanup456');

    expect($compiled)->toContain('unset($__blockInstancecleanup456, $__blockViewcleanup456);');
    expect($compiled)->toContain('unset($__blockViewDatacleanup456);');
});

test('sets block data if method exists', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'test-block',
        slug: 'test-block',
        class: TestBlock::class,
        name: 'Test Block'
    );
    $compiled = $compiler->compile($schema, 'data789');

    expect($compiled)->toContain('if (method_exists($__blockInstancedata789, \'setBlockData\')) {');
    expect($compiled)->toContain('$__blockInstancedata789->setBlockData($__blockDatadata789);');
});

test('sets context if method exists', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'test-block',
        slug: 'test-block',
        class: TestBlock::class,
        name: 'Test Block'
    );
    $compiled = $compiler->compile($schema, 'context101');

    expect($compiled)->toContain('if (method_exists($__blockInstancecontext101, \'setContext\')) {');
    expect($compiled)->toContain('$__blockInstancecontext101->setContext($__contextcontext101);');
});

test('generates unique variable names with hash', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'block',
        slug: 'block',
        class: TestBlock::class,
        name: 'Block'
    );
    $compiled1 = $compiler->compile($schema, 'unique1');
    $compiled2 = $compiler->compile($schema, 'unique2');

    // Check unique1 variables
    expect($compiled1)->toContain('$__blockInstanceunique1');
    expect($compiled1)->toContain('$__contextunique1');
    expect($compiled1)->toContain('$__blockViewunique1');

    // Check unique2 variables
    expect($compiled2)->toContain('$__blockInstanceunique2');
    expect($compiled2)->toContain('$__contextunique2');
    expect($compiled2)->toContain('$__blockViewunique2');

    // Ensure no cross-contamination
    expect($compiled1)->not->toContain('unique2');
    expect($compiled2)->not->toContain('unique1');
});

test('handles custom attributes expression with default', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
        type: 'test-block',
        slug: 'test-block',
        class: TestBlock::class,
        name: 'Test Block'
    );
    $compiled = $compiler->compile($schema, 'default123');

    // Should include the default empty array when no custom attributes provided
    expect($compiled)->toContain('[]');
});
