<?php

use Craftile\Laravel\View\NodeTransformers\CraftileBlockTagTransformer;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Components\ParameterNode;

beforeEach(function () {
    config([
        'craftile.components.namespace' => 'craftile',
    ]);
});

function createMockComponentNode(string $prefix, string $tagName, array $parameters = []): ComponentNode
{
    $node = new ComponentNode();
    $node->componentPrefix = $prefix;
    $node->tagName = $tagName;
    $node->parameters = [];

    foreach ($parameters as $name => $value) {
        $param = new ParameterNode();
        $param->name = $name;
        $param->value = $value;
        $node->parameters[] = $param;
    }

    return $node;
}

test('supports craftile block component tags', function () {
    $transformer = new CraftileBlockTagTransformer();

    expect($transformer->supports(createMockComponentNode('craftile', 'block')))->toBeTrue();
    expect($transformer->supports(createMockComponentNode('other', 'block')))->toBeFalse();
    expect($transformer->supports(createMockComponentNode('craftile', 'content')))->toBeFalse();
});

test('uses custom configured component namespace', function () {
    config(['craftile.components.namespace' => 'builder']);

    $transformer = new CraftileBlockTagTransformer();

    expect($transformer->supports(createMockComponentNode('builder', 'block')))->toBeTrue();
    expect($transformer->supports(createMockComponentNode('craftile', 'block')))->toBeFalse();
});

test('implements node transformer interface', function () {
    $transformer = new CraftileBlockTagTransformer();

    expect($transformer)->toBeInstanceOf(\Craftile\Laravel\Contracts\NodeTransformerInterface::class);
});

test('returns original node for non-component nodes', function () {
    $transformer = new CraftileBlockTagTransformer();
    $nonComponentNode = new \Stillat\BladeParser\Nodes\DirectiveNode();

    $result = $transformer->transform($nonComponentNode, null);

    expect($result)->toBe($nonComponentNode);
});

test('can access component parameters', function () {
    $node = createMockComponentNode('craftile', 'block', [
        'type' => '"text"',
        'id' => '"my-block"',
        'class' => '"custom-class"',
    ]);

    // This test verifies the component node structure
    expect($node->parameters)->toHaveCount(3);
    expect($node->parameters[0]->name)->toBe('type');
    expect($node->parameters[1]->name)->toBe('id');
    expect($node->parameters[2]->name)->toBe('class');
});

test('handles block component without closing tag', function () {
    $transformer = new CraftileBlockTagTransformer();
    $node = createMockComponentNode('craftile', 'block');
    $node->isClosingTag = false;

    expect($transformer->supports($node))->toBeTrue();
});

test('handles empty parameter list', function () {
    $transformer = new CraftileBlockTagTransformer();
    $node = createMockComponentNode('craftile', 'block');

    expect($transformer->supports($node))->toBeTrue();
    expect($node->parameters)->toBeEmpty();
});
