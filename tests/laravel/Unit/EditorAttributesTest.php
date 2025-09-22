<?php

declare(strict_types=1);

use Craftile\Laravel\BlockData;
use Craftile\Laravel\EditorAttributes;
use Illuminate\Contracts\Support\Htmlable;

describe('EditorAttributes', function () {
    beforeEach(function () {
        $this->blockData = BlockData::make([
            'id' => 'test-block-123',
            'type' => 'text',
            'properties' => ['content' => 'Test content'],
        ]);
    });

    it('implements Htmlable interface', function () {
        $editorAttributes = new EditorAttributes($this->blockData);

        expect($editorAttributes)->toBeInstanceOf(Htmlable::class);
    });

    it('returns block attributes when in preview mode', function () {
        $editorAttributes = new EditorAttributes($this->blockData, inPreview: true);

        $html = $editorAttributes->toHtml();

        expect($html)->toBe('data-block="test-block-123"');
    });

    it('returns empty string when not in preview mode', function () {
        $editorAttributes = new EditorAttributes($this->blockData, inPreview: false);

        $html = $editorAttributes->toHtml();

        expect($html)->toBe('');
    });

    it('escapes HTML in block ID', function () {
        $blockData = BlockData::make([
            'id' => 'test-block-<script>alert("xss")</script>',
            'type' => 'text',
            'properties' => [],
        ]);

        $editorAttributes = new EditorAttributes($blockData, inPreview: true);

        $html = $editorAttributes->toHtml();

        expect($html)->toBe('data-block="test-block-&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;"');
    });

    it('can be cast to string', function () {
        $editorAttributes = new EditorAttributes($this->blockData, inPreview: true);

        $string = (string) $editorAttributes;

        expect($string)->toBe('data-block="test-block-123"');
    });
});
