<?php

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\View\Compilers\DefaultBlockCompiler;

test('supports any block schema', function () {
    $compiler = new DefaultBlockCompiler;

    $schema = new BlockSchema(
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

    $compiled = $compiler->compile('text-block', 'abc123');

    expect($compiled)->toContain('$__blockSchemaabc123 = craftile()->getBlockSchema("text-block");');
    expect($compiled)->toContain('$__blockInstanceabc123 = new $__blockSchemaabc123->class;');
    expect($compiled)->toContain('$__childrenabc123 = null;');
    expect($compiled)->toContain('if (method_exists($__blockInstanceabc123, \'setBlockData\'))');
    expect($compiled)->toContain('if (method_exists($__blockInstanceabc123, \'setContext\'))');
    expect($compiled)->toContain('$__blockViewabc123 = $__blockInstanceabc123->render();');
});

test('compiles block with children closure', function () {
    $compiler = new DefaultBlockCompiler;

    $childrenCode = 'fn() => collect($children)->map(fn($c) => render($c))';
    $compiled = $compiler->compile('container-block', 'def456', $childrenCode);

    expect($compiled)->toContain("\$__childrendef456 = {$childrenCode};");
    expect($compiled)->not->toContain('$__childrendef456 = null;');
});

test('compiles block with custom attributes', function () {
    $compiler = new DefaultBlockCompiler;

    $customAttrs = "['variant' => 'primary', 'size' => 'large']";
    $compiled = $compiler->compile('button-block', 'ghi789', '', $customAttrs);

    expect($compiled)->toContain('array_merge(');
    expect($compiled)->toContain($customAttrs);
    expect($compiled)->toContain('$__contextghi789 = array_merge(');
});

test('includes context filtering logic', function () {
    $compiler = new DefaultBlockCompiler;

    $compiled = $compiler->compile('test-block', 'filter123');

    expect($compiled)->toContain('array_filter(get_defined_vars()');
    expect($compiled)->toContain("fn(\$_, \$key) => !str_starts_with(\$key, '__')");
    expect($compiled)->toContain("|| \$key === '__staticBlocksChildren'");
    expect($compiled)->toContain('ARRAY_FILTER_USE_BOTH');
});

test('handles view rendering and output', function () {
    $compiler = new DefaultBlockCompiler;

    $compiled = $compiler->compile('test-block', 'render123');

    expect($compiled)->toContain('if($__blockViewrender123 instanceof \\Illuminate\\View\\View)');
    expect($compiled)->toContain('$__blockViewDatarender123 = array_merge(');
    expect($compiled)->toContain("['block' => \$__blockDatarender123, 'children' => \$__childrenrender123]");
    expect($compiled)->toContain('echo $__blockViewrender123->with($__blockViewDatarender123)->render();');
    expect($compiled)->toContain('} else {');
    expect($compiled)->toContain('echo $__blockViewrender123;');
});

test('includes variable cleanup', function () {
    $compiler = new DefaultBlockCompiler;

    $compiled = $compiler->compile('test-block', 'cleanup456');

    expect($compiled)->toContain('unset($__blockSchemacleanup456, $__blockInstancecleanup456, $__blockViewcleanup456);');
    expect($compiled)->toContain('unset($__blockViewDatacleanup456);');
});

test('sets block data if method exists', function () {
    $compiler = new DefaultBlockCompiler;

    $compiled = $compiler->compile('test-block', 'data789');

    expect($compiled)->toContain('if (method_exists($__blockInstancedata789, \'setBlockData\')) {');
    expect($compiled)->toContain('$__blockInstancedata789->setBlockData($__blockDatadata789);');
});

test('sets context if method exists', function () {
    $compiler = new DefaultBlockCompiler;

    $compiled = $compiler->compile('test-block', 'context101');

    expect($compiled)->toContain('if (method_exists($__blockInstancecontext101, \'setContext\')) {');
    expect($compiled)->toContain('$__blockInstancecontext101->setContext($__contextcontext101);');
});

test('generates unique variable names with hash', function () {
    $compiler = new DefaultBlockCompiler;

    $compiled1 = $compiler->compile('block', 'unique1');
    $compiled2 = $compiler->compile('block', 'unique2');

    // Check unique1 variables
    expect($compiled1)->toContain('$__blockSchemaunique1');
    expect($compiled1)->toContain('$__blockInstanceunique1');
    expect($compiled1)->toContain('$__contextunique1');
    expect($compiled1)->toContain('$__blockViewunique1');
    expect($compiled1)->toContain('$__childrenunique1');

    // Check unique2 variables
    expect($compiled2)->toContain('$__blockSchemaunique2');
    expect($compiled2)->toContain('$__blockInstanceunique2');
    expect($compiled2)->toContain('$__contextunique2');
    expect($compiled2)->toContain('$__blockViewunique2');
    expect($compiled2)->toContain('$__childrenunique2');

    // Ensure no cross-contamination
    expect($compiled1)->not->toContain('unique2');
    expect($compiled2)->not->toContain('unique1');
});

test('handles custom attributes expression with default', function () {
    $compiler = new DefaultBlockCompiler;

    $compiled = $compiler->compile('test-block', 'default123');

    // Should include the default empty array when no custom attributes provided
    expect($compiled)->toContain('[]');
});
