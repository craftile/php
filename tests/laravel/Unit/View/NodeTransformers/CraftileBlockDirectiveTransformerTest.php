<?php

use Craftile\Laravel\View\NodeTransformers\CraftileBlockDirectiveTransformer;
use Stillat\BladeParser\Nodes\ArgumentGroupNode;
use Stillat\BladeParser\Nodes\DirectiveNode;

beforeEach(function () {
    // Mock craftile configuration for testing
    config([
        'craftile.directives' => [
            'craftileBlock' => 'craftileBlock',
        ],
    ]);
});

function createMockDirectiveNode(string $directive, array $args = []): DirectiveNode
{
    $node = new DirectiveNode;
    $node->content = $directive;
    $node->arguments = null;

    if (! empty($args)) {
        $argumentGroup = new ArgumentGroupNode($node);
        $argumentGroup->innerContent = implode(', ', $args);
        $node->arguments = $argumentGroup;
    }

    return $node;
}

test('supports configured craftile block directive variants', function () {
    $transformer = new CraftileBlockDirectiveTransformer;

    // Test all variants
    expect($transformer->supports(createMockDirectiveNode('craftileBlock')))->toBeTrue();
    expect($transformer->supports(createMockDirectiveNode('craftileblock')))->toBeTrue();
    expect($transformer->supports(createMockDirectiveNode('craftile_block')))->toBeTrue();
});

test('does not support non-craftile directives', function () {
    $transformer = new CraftileBlockDirectiveTransformer;

    expect($transformer->supports(createMockDirectiveNode('section')))->toBeFalse();
    expect($transformer->supports(createMockDirectiveNode('yield')))->toBeFalse();
    expect($transformer->supports(createMockDirectiveNode('include')))->toBeFalse();
});

test('supports custom configured directive name', function () {
    config(['craftile.directives.craftileBlock' => 'builderBlock']);

    $transformer = new CraftileBlockDirectiveTransformer;

    expect($transformer->supports(createMockDirectiveNode('builderBlock')))->toBeTrue();
    expect($transformer->supports(createMockDirectiveNode('builderblock')))->toBeTrue();
    expect($transformer->supports(createMockDirectiveNode('builder_block')))->toBeTrue();
    expect($transformer->supports(createMockDirectiveNode('craftileBlock')))->toBeFalse();
});

test('can parse arguments from directive node', function () {
    $transformer = new CraftileBlockDirectiveTransformer;

    $node = createMockDirectiveNode('craftileBlock', ['"text"', '"test-id"', '[]']);

    // Test that arguments are correctly set up
    expect($node->arguments)->not->toBeNull();
    expect($node->arguments->getArgValues())->toHaveCount(3);
});

test('handles nodes without arguments', function () {
    $transformer = new CraftileBlockDirectiveTransformer;

    $nodeNoArgs = createMockDirectiveNode('craftileBlock');

    // Test that node without arguments has null arguments
    expect($nodeNoArgs->arguments)->toBeNull();
});

test('can extract directive content', function () {
    $transformer = new CraftileBlockDirectiveTransformer;

    $node = createMockDirectiveNode('craftileBlock');

    expect($node->content)->toBe('craftileBlock');
});

test('directive transformer implements node transformer interface', function () {
    $transformer = new CraftileBlockDirectiveTransformer;

    expect($transformer)->toBeInstanceOf(\Craftile\Laravel\Contracts\NodeTransformerInterface::class);
});

test('extracts custom attributes from third parameter', function () {
    $node = createMockDirectiveNode('craftileBlock', ['"text"', '"test-id"', "['class' => 'custom-class']"]);

    expect($node->arguments->getArgValues())->toHaveCount(3);
    expect($node->arguments->getArgValues()[2])->toBe("['class' => 'custom-class']");
});

test('uses empty array as default when no custom attributes provided', function () {
    $node = createMockDirectiveNode('craftileBlock', ['"text"', '"test-id"']);

    // Only 2 arguments, third should default to []
    expect($node->arguments->getArgValues())->toHaveCount(2);
});

test('handles complex custom attributes expressions', function () {
    $customAttrs = "['class' => \$className, 'data-id' => \$itemId, 'style' => 'color: red']";
    $node = createMockDirectiveNode('craftileBlock', ['"hero"', '"hero-1"', $customAttrs]);

    expect($node->arguments->getArgValues())->toHaveCount(3);
    expect($node->arguments->getArgValues()[2])->toBe($customAttrs);
});
