<?php

use Craftile\Laravel\View\NodeTransformers\Concerns\HandlesLiterals;
use Stillat\BladeParser\Nodes\LiteralNode;

// Create a test class that uses the trait
class TestHandlesLiterals
{
    use HandlesLiterals;

    // Make protected methods public for testing
    public function testCreateLiteralNode(string $content): LiteralNode
    {
        return $this->createLiteralNode($content);
    }

    public function testUnquoteLiteral(string $expr): ?string
    {
        return $this->unquoteLiteral($expr);
    }
}

beforeEach(function () {
    $this->handler = new TestHandlesLiterals;
});

test('creates literal node with content', function () {
    $content = '<div>Test content</div>';
    $node = $this->handler->testCreateLiteralNode($content);

    expect($node)->toBeInstanceOf(LiteralNode::class);
    expect($node->content)->toBe($content);
});

test('unquotes single quoted strings', function () {
    expect($this->handler->testUnquoteLiteral("'hello'"))->toBe('hello');
    expect($this->handler->testUnquoteLiteral("'hello world'"))->toBe('hello world');
    expect($this->handler->testUnquoteLiteral("'with spaces'"))->toBe('with spaces');
});

test('unquotes double quoted strings', function () {
    expect($this->handler->testUnquoteLiteral('"hello"'))->toBe('hello');
    expect($this->handler->testUnquoteLiteral('"hello world"'))->toBe('hello world');
    expect($this->handler->testUnquoteLiteral('"with spaces"'))->toBe('with spaces');
});

test('handles escaped quotes in single quoted strings', function () {
    expect($this->handler->testUnquoteLiteral("'don\\'t'"))->toBe("don't");
    expect($this->handler->testUnquoteLiteral("'it\\'s here'"))->toBe("it's here");
});

test('handles escaped quotes in double quoted strings', function () {
    expect($this->handler->testUnquoteLiteral('"say \\"hello\\""'))->toBe('say "hello"');
    expect($this->handler->testUnquoteLiteral('"she said \\"hi\\""'))->toBe('she said "hi"');
});

test('handles escaped backslashes', function () {
    expect($this->handler->testUnquoteLiteral("'path\\\\to\\\\file'"))->toBe('path\\to\\file');
    expect($this->handler->testUnquoteLiteral('"path\\\\to\\\\file"'))->toBe('path\\to\\file');
});

test('returns null for non-quoted strings', function () {
    expect($this->handler->testUnquoteLiteral('hello'))->toBeNull();
    expect($this->handler->testUnquoteLiteral('variable'))->toBeNull();
    expect($this->handler->testUnquoteLiteral('$variable'))->toBeNull();
    expect($this->handler->testUnquoteLiteral('function()'))->toBeNull();
});

test('returns null for mismatched quotes', function () {
    expect($this->handler->testUnquoteLiteral("'hello\""))->toBeNull();
    expect($this->handler->testUnquoteLiteral("\"hello'"))->toBeNull();
    expect($this->handler->testUnquoteLiteral("'hello"))->toBeNull();
    expect($this->handler->testUnquoteLiteral("hello'"))->toBeNull();
});

test('returns null for empty string', function () {
    expect($this->handler->testUnquoteLiteral(''))->toBeNull();
});

test('handles whitespace around quotes', function () {
    expect($this->handler->testUnquoteLiteral("  'hello'  "))->toBe('hello');
    expect($this->handler->testUnquoteLiteral("\t\"world\"\n"))->toBe('world');
});

test('handles empty quoted strings', function () {
    expect($this->handler->testUnquoteLiteral("''"))->toBe('');
    expect($this->handler->testUnquoteLiteral('""'))->toBe('');
});

test('handles strings with only quotes', function () {
    expect($this->handler->testUnquoteLiteral("'\"'"))->toBe('"');
    expect($this->handler->testUnquoteLiteral("\"'\""))->toBe("'");
});

test('handles complex escaped content', function () {
    expect($this->handler->testUnquoteLiteral("'line1\\nline2'"))->toBe('line1\\nline2'); // No newline unescaping
    expect($this->handler->testUnquoteLiteral('"tab\\ttab"'))->toBe('tab\\ttab'); // No tab unescaping
});

test('handles single character quoted strings', function () {
    expect($this->handler->testUnquoteLiteral("'a'"))->toBe('a');
    expect($this->handler->testUnquoteLiteral('"b"'))->toBe('b');
});

test('handles unicode content', function () {
    expect($this->handler->testUnquoteLiteral("'héllo'"))->toBe('héllo');
    expect($this->handler->testUnquoteLiteral('"wörld"'))->toBe('wörld');
    expect($this->handler->testUnquoteLiteral("'🎉'"))->toBe('🎉');
});
