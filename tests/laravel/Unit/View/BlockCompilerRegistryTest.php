<?php

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\Contracts\BlockCompilerInterface;
use Craftile\Laravel\View\BlockCompilerRegistry;

class MockBlockCompiler implements BlockCompilerInterface
{
    public function compile(BlockSchema $schema, string $hash, string $childrenClosureCode = '', string $customAttributesExpr = ''): string
    {
        return '<div class="mock-block">Mock content</div>';
    }

    public function supports(BlockSchema $schema): bool
    {
        return $schema->slug === 'mock-block';
    }
}

class AnotherMockCompiler implements BlockCompilerInterface
{
    public function compile(BlockSchema $schema, string $hash, string $childrenClosureCode = '', string $customAttributesExpr = ''): string
    {
        return '<span class="another-mock">Another mock</span>';
    }

    public function supports(BlockSchema $schema): bool
    {
        return $schema->slug === 'another-block';
    }
}

test('can register and find block compiler', function () {
    $registry = app(BlockCompilerRegistry::class);
    $compiler = new MockBlockCompiler;

    $registry->register($compiler);

    $schema = new BlockSchema(
        type: 'mock-block',
        slug: 'mock-block',
        class: 'MockBlock',
        name: 'Mock Block',
        description: 'A mock block',
        icon: 'mock',
        category: 'test'
    );

    $foundCompiler = $registry->findCompiler($schema);
    expect($foundCompiler)->toBe($compiler);
});

test('returns default compiler for unsupported schema', function () {
    $registry = app(BlockCompilerRegistry::class);

    $schema = new BlockSchema(
        type: 'unsupported-block',
        slug: 'unsupported-block',
        class: 'UnsupportedBlock',
        name: 'Unsupported Block',
        description: 'An unsupported block',
        icon: 'unsupported',
        category: 'test'
    );

    $compiler = $registry->findCompiler($schema);
    expect($compiler)->toBeInstanceOf(BlockCompilerInterface::class);

    // Should use default compiler
    $compiled = $compiler->compile($schema, 'test-hash');
    expect($compiled)->toContain($schema->class);
});

test('finds most specific compiler', function () {
    $registry = app(BlockCompilerRegistry::class);

    $compiler1 = new MockBlockCompiler;
    $compiler2 = new AnotherMockCompiler;

    $registry->register($compiler1);
    $registry->register($compiler2);

    $schema1 = new BlockSchema(
        type: 'mock-block',
        slug: 'mock-block',
        class: 'MockBlock',
        name: 'Mock Block',
        description: 'A mock block',
        icon: 'mock',
        category: 'test'
    );

    $schema2 = new BlockSchema(
        type: 'another-block',
        slug: 'another-block',
        class: 'AnotherBlock',
        name: 'Another Block',
        description: 'Another block',
        icon: 'another',
        category: 'test'
    );

    expect($registry->findCompiler($schema1))->toBe($compiler1);
    expect($registry->findCompiler($schema2))->toBe($compiler2);
});

test('can get all registered compilers', function () {
    $registry = new \Craftile\Laravel\View\BlockCompilerRegistry;

    $compiler1 = new MockBlockCompiler;
    $compiler2 = new AnotherMockCompiler;

    $registry->register($compiler1);
    $registry->register($compiler2);

    $compilers = $registry->getCompilers();
    expect($compilers)->toHaveCount(2); // Only registered compilers, default is separate
    expect($compilers)->toContain($compiler1);
    expect($compilers)->toContain($compiler2);
});
