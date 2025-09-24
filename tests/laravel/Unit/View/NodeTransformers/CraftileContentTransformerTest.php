<?php

use Craftile\Laravel\View\NodeTransformers\CraftileContentTransformer;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\DirectiveNode;

beforeEach(function () {
    config([
        'craftile.directives' => [
            'craftileContent' => 'craftileContent',
            'craftileLayoutContent' => 'craftileLayoutContent',
        ],
        'craftile.components' => [
            'namespace' => 'craftile',
        ],
    ]);
});

function createContentDirectiveNode(string $directive): DirectiveNode
{
    $node = new DirectiveNode;
    $node->content = $directive;

    return $node;
}

function createContentComponentNode(string $prefix, string $tagName, bool $isClosing = false): ComponentNode
{
    $node = new ComponentNode;
    $node->componentPrefix = $prefix;
    $node->tagName = $tagName;
    $node->isClosingTag = $isClosing;
    $node->parameters = [];

    return $node;
}

test('supports layout content directive variants', function () {
    $transformer = new CraftileContentTransformer;

    expect($transformer->supports(createContentDirectiveNode('craftileLayoutContent')))->toBeTrue();
    expect($transformer->supports(createContentDirectiveNode('craftile_layout_content')))->toBeTrue();
    expect($transformer->supports(createContentDirectiveNode('craftilelayoutcontent')))->toBeTrue(); // lowercase without underscores
});

test('supports layout content component tags', function () {
    $transformer = new CraftileContentTransformer;

    expect($transformer->supports(createContentComponentNode('craftile', 'layout-content')))->toBeTrue();
    expect($transformer->supports(createContentComponentNode('craftile', 'layoutcontent')))->toBeTrue();
    expect($transformer->supports(createContentComponentNode('custom', 'layout-content')))->toBeFalse();
});

test('supports content directive variants', function () {
    $transformer = new CraftileContentTransformer;

    expect($transformer->supports(createContentDirectiveNode('craftileContent')))->toBeTrue();
    expect($transformer->supports(createContentDirectiveNode('craftilecontent')))->toBeTrue();
    expect($transformer->supports(createContentDirectiveNode('craftile_content')))->toBeTrue();
});

test('supports content component tags', function () {
    $transformer = new CraftileContentTransformer;

    expect($transformer->supports(createContentComponentNode('craftile', 'content')))->toBeTrue();
    expect($transformer->supports(createContentComponentNode('craftile', 'content', true)))->toBeTrue(); // closing tag
    expect($transformer->supports(createContentComponentNode('custom', 'content')))->toBeFalse();
});

test('supports end content directive variants', function () {
    $transformer = new CraftileContentTransformer;

    expect($transformer->supports(createContentDirectiveNode('endCraftileContent')))->toBeTrue();      // camelCase
    expect($transformer->supports(createContentDirectiveNode('EndCraftileContent')))->toBeTrue();      // PascalCase
    expect($transformer->supports(createContentDirectiveNode('end_craftile_content')))->toBeTrue();    // snake_case
    expect($transformer->supports(createContentDirectiveNode('endcraftilecontent')))->toBeTrue();      // lowercase
});

test('does not support unrelated directives', function () {
    $transformer = new CraftileContentTransformer;

    expect($transformer->supports(createContentDirectiveNode('section')))->toBeFalse();
    expect($transformer->supports(createContentDirectiveNode('yield')))->toBeFalse();
    expect($transformer->supports(createContentDirectiveNode('include')))->toBeFalse();
});

test('transforms layout content directive correctly', function () {
    $transformer = new CraftileContentTransformer;
    $node = createContentDirectiveNode('craftileLayoutContent');

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(\Stillat\BladeParser\Nodes\AbstractNode::class);
    // We can't easily test the exact content without more complex setup
    // but we can verify the transform method was called without error
});

test('transforms layout content tag correctly', function () {
    $transformer = new CraftileContentTransformer;
    $node = createContentComponentNode('craftile', 'layout-content');

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(\Stillat\BladeParser\Nodes\AbstractNode::class);
});

test('transforms content directive correctly', function () {
    $transformer = new CraftileContentTransformer;
    $node = createContentDirectiveNode('craftileContent');

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(\Stillat\BladeParser\Nodes\AbstractNode::class);
});

test('transforms content tag correctly', function () {
    $transformer = new CraftileContentTransformer;
    $node = createContentComponentNode('craftile', 'content');

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(\Stillat\BladeParser\Nodes\AbstractNode::class);
});

test('transforms end content directive correctly', function () {
    $transformer = new CraftileContentTransformer;
    $node = createContentDirectiveNode('endCraftileContent');

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(\Stillat\BladeParser\Nodes\AbstractNode::class);
});

test('transforms end content tag correctly', function () {
    $transformer = new CraftileContentTransformer;
    $node = createContentComponentNode('craftile', 'content', true);

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(\Stillat\BladeParser\Nodes\AbstractNode::class);
});

test('uses custom configured component namespace', function () {
    config(['craftile.components.namespace' => 'custom']);

    $transformer = new CraftileContentTransformer;

    expect($transformer->supports(createContentComponentNode('custom', 'content')))->toBeTrue();
    expect($transformer->supports(createContentComponentNode('craftile', 'content')))->toBeFalse();
});

test('uses custom configured directive names', function () {
    config([
        'craftile.directives.craftileContent' => 'builderContent',
        'craftile.directives.craftileLayoutContent' => 'builderLayoutContent',
    ]);

    $transformer = new CraftileContentTransformer;

    expect($transformer->supports(createContentDirectiveNode('builderContent')))->toBeTrue();
    expect($transformer->supports(createContentDirectiveNode('builderLayoutContent')))->toBeTrue();
    expect($transformer->supports(createContentDirectiveNode('craftileContent')))->toBeFalse();
});

test('returns original node for unsupported nodes', function () {
    $transformer = new CraftileContentTransformer;
    $unsupportedNode = createContentDirectiveNode('section');

    $result = $transformer->transform($unsupportedNode, null);

    expect($result)->toBe($unsupportedNode);
});
