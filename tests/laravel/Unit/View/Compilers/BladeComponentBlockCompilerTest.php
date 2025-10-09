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
        $compiled = $this->compiler->compile($schema, 'vwx234', "['attr' => 'value']");

        // Basic syntax checks
        expect($compiled)->toContain('<?php');
        expect($compiled)->toContain('?>');
        expect($compiled)->toContain(';'); // Statements end with semicolons
        expect($compiled)->not->toContain(';;'); // No double semicolons
    });
});

describe('Custom Attributes Integration for Blade Components', function () {
    beforeEach(function () {
        $this->compiler = new BladeComponentBlockCompiler;
    });

    it('passes custom attributes through filterContext', function () {
        $schema = new BlockSchema(
            type: 'alert',
            slug: 'alert',
            class: TestBladeComponent::class,
            name: 'Alert Component'
        );

        $customAttrs = "['variant' => 'danger', 'dismissible' => true]";
        $compiled = $this->compiler->compile($schema, 'alert123', $customAttrs);

        // Custom attributes should be passed to filterContext
        expect($compiled)->toContain("craftile()->filterContext(get_defined_vars(), ['variant' => 'danger', 'dismissible' => true])");
    });

    it('makes custom attributes available via context variable', function () {
        $schema = new BlockSchema(
            type: 'modal',
            slug: 'modal',
            class: TestBladeComponent::class,
            name: 'Modal Component'
        );

        $customAttrs = "['size' => 'large']";
        $compiled = $this->compiler->compile($schema, 'modal456', $customAttrs);

        // Context variable should be created
        expect($compiled)->toContain('$__contextmodal456 = craftile()->filterContext(');

        // Context should be passed to component
        expect($compiled)->toContain(':context="$__contextmodal456"');
    });

    it('injects custom attributes into PropertyBag context', function () {
        $schema = new BlockSchema(
            type: 'card',
            slug: 'card',
            class: TestBladeComponent::class,
            name: 'Card Component'
        );

        $customAttrs = "['elevation' => 2]";
        $compiled = $this->compiler->compile($schema, 'card789', $customAttrs);

        // PropertyBag should receive context with custom attributes
        expect($compiled)->toContain('$__blockDatacard789->properties->setContext($__contextcard789);');
    });

    it('passes custom attributes to Blade component via context prop', function () {
        $schema = new BlockSchema(
            type: 'button',
            slug: 'button',
            class: TestBladeComponent::class,
            name: 'Button Component'
        );

        $customAttrs = "['icon' => 'check', 'loading' => false]";
        $compiled = $this->compiler->compile($schema, 'btn101', $customAttrs);

        // Component tag should include context binding
        expect($compiled)->toContain('<x-craftile-button');
        expect($compiled)->toContain(':context="$__contextbtn101"');
    });

    it('handles dynamic custom attributes expressions', function () {
        $schema = new BlockSchema(
            type: 'dynamic',
            slug: 'dynamic',
            class: TestBladeComponent::class,
            name: 'Dynamic Component'
        );

        $customAttrs = '$componentAttrs';
        $compiled = $this->compiler->compile($schema, 'dyn202', $customAttrs);

        // Should accept variable expressions
        expect($compiled)->toContain('craftile()->filterContext(get_defined_vars(), $componentAttrs)');
    });

    it('cleans up context variable after rendering', function () {
        $schema = new BlockSchema(
            type: 'cleanup',
            slug: 'cleanup',
            class: TestBladeComponent::class,
            name: 'Cleanup Component'
        );

        $customAttrs = "['test' => 'value']";
        $compiled = $this->compiler->compile($schema, 'clean303', $customAttrs);

        // Context variable should be unset
        expect($compiled)->toContain('unset($__contextclean303);');
    });

    it('preserves block data with custom attributes context', function () {
        $schema = new BlockSchema(
            type: 'preserve',
            slug: 'preserve',
            class: TestBladeComponent::class,
            name: 'Preserve Component'
        );

        $customAttrs = "['preserve' => true]";
        $compiled = $this->compiler->compile($schema, 'pres404', $customAttrs);

        // Block should be passed to component
        expect($compiled)->toContain(':block="$__blockDatapres404"');

        // Context should be set before component rendering
        expect($compiled)->toContain('$__blockDatapres404->properties->setContext($__contextpres404);');
    });
});
