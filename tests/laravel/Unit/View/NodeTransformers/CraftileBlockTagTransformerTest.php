<?php

use Craftile\Laravel\BlockSchemaRegistry;
use Craftile\Laravel\View\NodeTransformers\CraftileBlockTagTransformer;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Components\ParameterAttribute;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;
use Stillat\BladeParser\Nodes\LiteralNode as BladeLiteralNode;

beforeEach(function () {
    config([
        'craftile.components.namespace' => 'craftile',
    ]);

    // Register test block schema
    app(BlockSchemaRegistry::class)->register(sampleBlockSchema());
});

function createMockComponentNode(string $prefix, string $tagName, array $parameters = []): ComponentNode
{
    $node = new ComponentNode;
    $node->componentPrefix = $prefix;
    $node->tagName = $tagName;
    $node->parameters = [];

    foreach ($parameters as $name => $value) {
        $param = new ParameterNode;
        $param->name = $name;
        $param->materializedName = ltrim($name, ':');

        // Check if this is a dynamic binding (:attribute)
        if (str_starts_with($name, ':')) {
            $param->type = ParameterType::DynamicVariable;
            // For dynamic bindings, wrap value in quotes like Blade Parser does
            $param->value = '"'.$value.'"';
        } else {
            $param->type = ParameterType::Parameter;
            $param->value = $value;

            // Create valueNode if value is a literal string
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                $valueAttr = new ParameterAttribute;
                $literalNode = new BladeLiteralNode;
                $literalNode->content = $value;
                $valueAttr->content = $literalNode;
                $param->valueNode = $valueAttr;
            }
        }

        $node->parameters[] = $param;
    }

    return $node;
}

test('supports craftile block component tags', function () {
    $transformer = new CraftileBlockTagTransformer;

    expect($transformer->supports(createMockComponentNode('craftile', 'block')))->toBeTrue();
    expect($transformer->supports(createMockComponentNode('other', 'block')))->toBeFalse();
    expect($transformer->supports(createMockComponentNode('craftile', 'content')))->toBeFalse();
});

test('uses custom configured component namespace', function () {
    config(['craftile.components.namespace' => 'builder']);

    $transformer = new CraftileBlockTagTransformer;

    expect($transformer->supports(createMockComponentNode('builder', 'block')))->toBeTrue();
    expect($transformer->supports(createMockComponentNode('craftile', 'block')))->toBeFalse();
});

test('implements node transformer interface', function () {
    $transformer = new CraftileBlockTagTransformer;

    expect($transformer)->toBeInstanceOf(\Craftile\Laravel\Contracts\NodeTransformerInterface::class);
});

test('returns original node for non-component nodes', function () {
    $transformer = new CraftileBlockTagTransformer;
    $nonComponentNode = new \Stillat\BladeParser\Nodes\DirectiveNode;

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
    $transformer = new CraftileBlockTagTransformer;
    $node = createMockComponentNode('craftile', 'block');
    $node->isClosingTag = false;

    expect($transformer->supports($node))->toBeTrue();
});

test('handles empty parameter list', function () {
    $transformer = new CraftileBlockTagTransformer;
    $node = createMockComponentNode('craftile', 'block');

    expect($transformer->supports($node))->toBeTrue();
    expect($node->parameters)->toBeEmpty();
});

test('compiled output includes custom attributes in properties expression', function () {
    $transformer = new CraftileBlockTagTransformer;
    $node = createMockComponentNode('craftile', 'block', [
        'type' => 'text',
        'id' => 'my-block',
        'class' => 'custom-class',
    ]);

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(BladeLiteralNode::class);
    expect($result->content)->toContain("'class' => custom-class");
});

test('compiled output passes multiple custom attributes correctly', function () {
    $transformer = new CraftileBlockTagTransformer;
    $node = createMockComponentNode('craftile', 'block', [
        'type' => 'text',
        'id' => 'hero-1',
        'class' => 'text-center',
        'data-id' => '123',
        'variant' => 'primary',
    ]);

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(BladeLiteralNode::class);
    expect($result->content)->toContain("'class' => text-center");
    expect($result->content)->toContain("'data-id' => 123");
    expect($result->content)->toContain("'variant' => primary");
});

test('compiled output handles dynamic bindings in custom attributes', function () {
    $transformer = new CraftileBlockTagTransformer;
    $node = createMockComponentNode('craftile', 'block', [
        'type' => 'text',
        'id' => 'card-1',
        ':class' => '$cardClass',
    ]);

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(BladeLiteralNode::class);
    expect($result->content)->toContain("'class' => \$cardClass");
});

test('compiled output uses empty array when only type and id provided', function () {
    $transformer = new CraftileBlockTagTransformer;
    $node = createMockComponentNode('craftile', 'block', [
        'type' => 'text',
        'id' => 'simple-block',
    ]);

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(BladeLiteralNode::class);
    // Should use empty array [] when no custom attributes
    expect($result->content)->toContain('craftile()->filterContext(get_defined_vars(), [])');
});

test('compiled output correctly strips quotes from dynamic bindings', function () {
    $transformer = new CraftileBlockTagTransformer;
    $node = createMockComponentNode('craftile', 'block', [
        'type' => 'text',
        'id' => 'test-1',
        ':variant' => '$myVariant',
        ':size' => '$item->size',
        ':class' => '$loop->even ? "even" : "odd"',
    ]);

    $result = $transformer->transform($node, null);

    expect($result)->toBeInstanceOf(BladeLiteralNode::class);
    // Should NOT have quotes around the dynamic expressions
    expect($result->content)->toContain("'variant' => \$myVariant");
    expect($result->content)->toContain("'size' => \$item->size");
    expect($result->content)->toContain("'class' => \$loop->even ? \"even\" : \"odd\"");
    // Should NOT contain the outer wrapping quotes that Blade Parser adds
    expect($result->content)->not->toContain('"$myVariant"');
});
