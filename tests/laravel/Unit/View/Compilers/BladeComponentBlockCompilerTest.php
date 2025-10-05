<?php

declare(strict_types=1);

use Craftile\Core\Data\BlockSchema;
use Craftile\Laravel\View\Compilers\BladeComponentBlockCompiler;
use Illuminate\View\Component;

beforeEach(function () {
    $this->compiler = new BladeComponentBlockCompiler;
});

// Test component class for testing
class TestBladeComponent extends Component
{
    public function render()
    {
        return '<div>Test Component</div>';
    }
}

// Non-component class for testing
class NotAComponent
{
    // This is not a component
}

describe('BladeComponentBlockCompiler', function () {
    it('supports block schemas with Laravel component classes', function () {
        $schema = new BlockSchema(
            'test-component',
            'test-component',
            TestBladeComponent::class,
            'Test Component'
        );

        expect($this->compiler->supports($schema))->toBeTrue();
    });

    it('does not support block schemas without class', function () {
        $schema = new BlockSchema(
            'test-block',
            'test-block',
            '', // empty class
            'Test Block'
        );

        expect($this->compiler->supports($schema))->toBeFalse();
    });

    it('does not support block schemas with non-component classes', function () {
        $schema = new BlockSchema(
            'not-component',
            'not-component',
            NotAComponent::class,
            'Not A Component'
        );

        expect($this->compiler->supports($schema))->toBeFalse();
    });

    it('compiles block with basic parameters', function () {
        $schema = new BlockSchema(
            type: 'hero',
            slug: 'hero',
            class: TestBladeComponent::class,
            name: 'Hero Block'
        );
        $compiled = $this->compiler->compile($schema, 'abc123');

        expect($compiled)->toContain('<x-craftile-hero');
        expect($compiled)->toContain(':block="$__blockDataabc123"');
        expect($compiled)->toContain(':context="$__contextabc123"');
    });

    it('compiles block with custom attributes', function () {
        $schema = new BlockSchema(
            type: 'hero',
            slug: 'hero',
            class: TestBladeComponent::class,
            name: 'Hero Block'
        );
        $customAttributes = "['class' => 'custom-class', 'id' => 'custom-id']";
        $compiled = $this->compiler->compile($schema, 'ghi789', $customAttributes);

        expect($compiled)->toContain('craftile()->filterContext(');
        expect($compiled)->toContain($customAttributes);
        expect($compiled)->toContain('<x-craftile-hero');
    });

    it('includes context filtering logic', function () {
        $schema = new BlockSchema(
            type: 'hero',
            slug: 'hero',
            class: TestBladeComponent::class,
            name: 'Hero Block'
        );
        $compiled = $this->compiler->compile($schema, 'jkl012');

        expect($compiled)->toContain('craftile()->filterContext(get_defined_vars()');
        expect($compiled)->toContain('$__contextjkl012');
    });

    it('includes variable cleanup', function () {
        $schema = new BlockSchema(
            type: 'hero',
            slug: 'hero',
            class: TestBladeComponent::class,
            name: 'Hero Block'
        );
        $compiled = $this->compiler->compile($schema, 'mno345');

        expect($compiled)->toContain('unset($__contextmno345);');
    });

    it('generates unique variable names with hash', function () {
        $schema = new BlockSchema(
            type: 'hero',
            slug: 'hero',
            class: TestBladeComponent::class,
            name: 'Hero Block'
        );
        $compiled1 = $this->compiler->compile($schema, 'hash1');
        $compiled2 = $this->compiler->compile($schema, 'hash2');

        expect($compiled1)->toContain('$__blockDatahash1');
        expect($compiled1)->toContain('$__contexthash1');

        expect($compiled2)->toContain('$__blockDatahash2');
        expect($compiled2)->toContain('$__contexthash2');

        expect($compiled1)->not->toContain('hash2');
        expect($compiled2)->not->toContain('hash1');
    });

    it('handles default custom attributes', function () {
        $schema = new BlockSchema(
            type: 'hero',
            slug: 'hero',
            class: TestBladeComponent::class,
            name: 'Hero Block'
        );
        $compiled = $this->compiler->compile($schema, 'stu901');

        expect($compiled)->toContain('craftile()->filterContext(');
        expect($compiled)->toContain('[]'); // Default empty array
    });

    it('passes context to component', function () {
        $schema = new BlockSchema(
            type: 'hero',
            slug: 'hero',
            class: TestBladeComponent::class,
            name: 'Hero Block'
        );
        $compiled = $this->compiler->compile($schema, 'ctx123');

        expect($compiled)->toContain(':context="$__contextctx123"');
    });

    it('produces valid PHP syntax', function () {
        $schema = new BlockSchema(
            type: 'hero',
            slug: 'hero',
            class: TestBladeComponent::class,
            name: 'Hero Block'
        );
        $compiled = $this->compiler->compile($schema, 'vwx234', 'function() { return "test"; }', "['attr' => 'value']");

        // Basic syntax checks
        expect($compiled)->toContain('<?php');
        expect($compiled)->toContain('?>');
        expect($compiled)->toContain(';'); // Statements end with semicolons
        expect($compiled)->not->toContain(';;'); // No double semicolons
    });
});
