<?php

use Craftile\Laravel\View\NodeTransformers\CraftileRegionDirectiveTransformer;
use Illuminate\View\ViewException;
use Stillat\BladeParser\Document\Document;
use Stillat\BladeParser\Nodes\ArgumentGroupNode;
use Stillat\BladeParser\Nodes\DirectiveNode;
use Stillat\BladeParser\Nodes\Position;

function createMockRegionDirectiveNode(string $directive, array $args = []): DirectiveNode
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

beforeEach(function () {
    $this->transformer = new CraftileRegionDirectiveTransformer;
    config(['craftile.directives' => ['craftileRegion' => 'craftileRegion']]);
});

it('supports craftileRegion directive', function () {
    $directive = createMockRegionDirectiveNode('craftileRegion');

    expect($this->transformer->supports($directive))->toBeTrue();
});

it('supports craftileregion directive (lowercase)', function () {
    $directive = createMockRegionDirectiveNode('craftileregion');

    expect($this->transformer->supports($directive))->toBeTrue();
});

it('supports craftile_region directive (snake_case)', function () {
    $directive = createMockRegionDirectiveNode('craftile_region');

    expect($this->transformer->supports($directive))->toBeTrue();
});

it('does not support other directives', function () {
    $directive = createMockRegionDirectiveNode('include');

    expect($this->transformer->supports($directive))->toBeFalse();
});

it('transforms craftileRegion to includeIf directive', function () {
    $directive = createMockRegionDirectiveNode('craftileRegion', ["'header'"]);

    $document = new Document;
    $result = $this->transformer->transform($directive, $document);

    expect($result->content)->toBe("<?php \$__regionView = craftile()->resolveRegionView('header'); ?>@includeIf(\$__regionView)<?php unset(\$__regionView); ?>");
});

it('preserves complex expressions in region name', function () {
    $directive = createMockRegionDirectiveNode('craftileRegion', ['$regionName']);

    $document = new Document;
    $result = $this->transformer->transform($directive, $document);

    expect($result->content)->toBe('<?php $__regionView = craftile()->resolveRegionView($regionName); ?>@includeIf($__regionView)<?php unset($__regionView); ?>');
});

it('handles custom directive names from config', function () {
    config(['craftile.directives.craftileRegion' => 'customRegion']);

    $directive = createMockRegionDirectiveNode('customRegion');

    expect($this->transformer->supports($directive))->toBeTrue();
});

it('throws error when no arguments provided', function () {
    $directive = createMockRegionDirectiveNode('craftileRegion');

    // Mock the position property that HandlesErrors trait expects
    $position = new Position;
    $position->startLine = 1;
    $directive->position = $position;

    $document = new Document;

    expect(fn () => $this->transformer->transform($directive, $document))
        ->toThrow(ViewException::class, '@craftileRegion requires one argument: region name');
});

it('throws error when empty arguments provided', function () {
    $directive = createMockRegionDirectiveNode('craftileRegion', []);

    // Mock the position property that HandlesErrors trait expects
    $position = new Position;
    $position->startLine = 1;
    $directive->position = $position;

    $document = new Document;

    expect(fn () => $this->transformer->transform($directive, $document))
        ->toThrow(ViewException::class, '@craftileRegion requires one argument: region name');
});
