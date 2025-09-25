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
            TestBladeComponent::class,
            'Test Component'
        );

        expect($this->compiler->supports($schema))->toBeTrue();
    });

    it('does not support block schemas without class', function () {
        $schema = new BlockSchema(
            'test-block',
            '', // empty class
            'Test Block'
        );

        expect($this->compiler->supports($schema))->toBeFalse();
    });

    it('does not support block schemas with non-component classes', function () {
        $schema = new BlockSchema(
            'not-component',
            NotAComponent::class,
            'Not A Component'
        );

        expect($this->compiler->supports($schema))->toBeFalse();
    });

    it('compiles block with basic parameters', function () {
        $compiled = $this->compiler->compile('hero', 'abc123');

        expect($compiled)->toContain('<x-craftile-hero');
        expect($compiled)->toContain(':block="$__blockDataabc123"');
        expect($compiled)->toContain(':context="$__contextabc123"');
        expect($compiled)->toContain(':children="$__childrenabc123"');
        expect($compiled)->toContain('$__childrenabc123 = null;');
    });

    it('compiles block with children closure', function () {
        $childrenClosure = 'function() { return "child content"; }';
        $compiled = $this->compiler->compile('hero', 'def456', $childrenClosure);

        expect($compiled)->toContain('$__childrendef456 = function() { return "child content"; };');
        expect($compiled)->toContain('<x-craftile-hero');
        expect($compiled)->toContain(':children="$__childrendef456"');
    });

    it('compiles block with custom attributes', function () {
        $customAttributes = "['class' => 'custom-class', 'id' => 'custom-id']";
        $compiled = $this->compiler->compile('hero', 'ghi789', '', $customAttributes);

        expect($compiled)->toContain('array_merge(');
        expect($compiled)->toContain($customAttributes);
        expect($compiled)->toContain('<x-craftile-hero');
    });

    it('includes context filtering logic', function () {
        $compiled = $this->compiler->compile('hero', 'jkl012');

        expect($compiled)->toContain('array_filter(get_defined_vars()');
        expect($compiled)->toContain("!str_starts_with(\$key, '__')");
        expect($compiled)->toContain("\$key === '__staticBlocksChildren'");
    });

    it('includes variable cleanup', function () {
        $compiled = $this->compiler->compile('hero', 'mno345');

        expect($compiled)->toContain('unset($__childrenmno345, $__contextmno345);');
    });

    it('generates unique variable names with hash', function () {
        $compiled1 = $this->compiler->compile('hero', 'hash1');
        $compiled2 = $this->compiler->compile('hero', 'hash2');

        expect($compiled1)->toContain('$__blockDatahash1');
        expect($compiled1)->toContain('$__childrenhash1');
        expect($compiled1)->toContain('$__contexthash1');

        expect($compiled2)->toContain('$__blockDatahash2');
        expect($compiled2)->toContain('$__childrenhash2');
        expect($compiled2)->toContain('$__contexthash2');

        expect($compiled1)->not->toContain('hash2');
        expect($compiled2)->not->toContain('hash1');
    });

    it('handles empty children closure properly', function () {
        $compiled = $this->compiler->compile('hero', 'pqr678', '');

        expect($compiled)->toContain('$__childrenpqr678 = null;');
        expect($compiled)->not->toContain('$__childrenpqr678 = ;');
    });

    it('handles default custom attributes', function () {
        $compiled = $this->compiler->compile('hero', 'stu901');

        expect($compiled)->toContain('array_merge(');
        expect($compiled)->toContain('[]'); // Default empty array
    });

    it('produces valid PHP syntax', function () {
        $compiled = $this->compiler->compile('hero', 'vwx234', 'function() { return "test"; }', "['attr' => 'value']");

        // Basic syntax checks
        expect($compiled)->toContain('<?php');
        expect($compiled)->toContain('?>');
        expect($compiled)->toContain(';'); // Statements end with semicolons
        expect($compiled)->not->toContain(';;'); // No double semicolons
    });
});
