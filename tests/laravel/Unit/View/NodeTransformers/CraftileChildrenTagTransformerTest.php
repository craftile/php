<?php

use Craftile\Laravel\View\NodeTransformers\CraftileChildrenTagTransformer;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\LiteralNode;
use Stillat\BladeParser\Nodes\Position;

function createMockChildrenComponentNode(string $prefix, array $parameters = []): ComponentNode
{
    $node = new ComponentNode();
    $node->componentPrefix = $prefix;
    $node->tagName = 'children';
    $node->parameters = [];

    foreach ($parameters as $name => $value) {
        $param = new ParameterNode();
        $param->name = $name;
        $param->value = $value;
        $node->parameters[] = $param;
    }

    return $node;
}

test('supports craftile children component tags', function () {
    $transformer = new CraftileChildrenTagTransformer();

    $node = createMockChildrenComponentNode('craftile');

    expect($transformer->supports($node))->toBeTrue();
});

test('does not support non-craftile component tags', function () {
    $transformer = new CraftileChildrenTagTransformer();

    $node = createMockChildrenComponentNode('other');

    expect($transformer->supports($node))->toBeFalse();
});

test('does not support non-children tags', function () {
    $transformer = new CraftileChildrenTagTransformer();

    $node = new ComponentNode();
    $node->componentPrefix = 'craftile';
    $node->tagName = 'block';

    expect($transformer->supports($node))->toBeFalse();
});

test('uses custom configured component namespace', function () {
    config(['craftile.components.namespace' => 'custom']);

    $transformer = new CraftileChildrenTagTransformer();

    $craftileNode = createMockChildrenComponentNode('craftile');
    $customNode = createMockChildrenComponentNode('custom');

    expect($transformer->supports($craftileNode))->toBeFalse();
    expect($transformer->supports($customNode))->toBeTrue();

    // Reset config
    config(['craftile.components.namespace' => 'craftile']);
});

test('transforms children tag to PHP code', function () {
    $transformer = new CraftileChildrenTagTransformer();

    $node = createMockChildrenComponentNode('craftile');
    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(LiteralNode::class);
    expect($result->content)->toContain('if(isset($children) && is_callable($children))');
    expect($result->content)->toContain('echo $children()');
});

test('throws error when children tag has parameters', function () {
    $transformer = new CraftileChildrenTagTransformer();

    $node = createMockChildrenComponentNode('craftile', ['invalid' => 'parameter']);

    // Mock the position property that HandlesErrors trait expects
    $position = new Position();
    $position->startLine = 1;
    $node->position = $position;

    expect(fn () => $transformer->transform($node, null))
        ->toThrow(Exception::class, '<craftile:children> tag should not have any attributes');
});

test('children transformer implements node transformer interface', function () {
    $transformer = new CraftileChildrenTagTransformer();

    expect($transformer)->toBeInstanceOf(\Craftile\Laravel\Contracts\NodeTransformerInterface::class);
});
