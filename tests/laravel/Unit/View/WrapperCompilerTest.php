<?php

use Craftile\Laravel\View\WrapperCompiler;

test('compiles basic wrapper with auto-appended content placeholder', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('div', 'block-123');

    expect($opening)->toBe('<div data-block="block-123">');
    expect($closing)->toBe('</div>');
});

test('compiles wrapper with existing content placeholder', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('div{__content__}', 'block-456');

    expect($opening)->toBe('<div data-block="block-456">');
    expect($closing)->toBe('</div>');
});

test('compiles wrapper with class', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('div.container', 'block-789');

    expect($opening)->toBe('<div data-block="block-789" class="container">');
    expect($closing)->toBe('</div>');
});

test('compiles wrapper with id', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('div#hero', 'block-abc');

    expect($opening)->toBe('<div data-block="block-abc" id="hero">');
    expect($closing)->toBe('</div>');
});

test('compiles wrapper with id and class', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('section#hero.container', 'block-def');

    expect($opening)->toBe('<section data-block="block-def" id="hero" class="container">');
    expect($closing)->toBe('</section>');
});

test('compiles wrapper with multiple classes', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('div.container.py-20.bg-gray-100', 'block-ghi');

    expect($opening)->toBe('<div data-block="block-ghi" class="container py-20 bg-gray-100">');
    expect($closing)->toBe('</div>');
});

test('compiles nested wrapper', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('section.wrapper>div.content', 'block-jkl');

    expect($opening)->toBe('<section data-block="block-jkl" class="wrapper"><div class="content">');
    expect($closing)->toBe('</div></section>');
});

test('compiles nested wrapper with content placeholder', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('section.wrapper>div.content{__content__}', 'block-mno');

    expect($opening)->toBe('<section data-block="block-mno" class="wrapper"><div class="content">');
    expect($closing)->toBe('</div></section>');
});

test('compiles complex nested wrapper', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('section#hero.py-20>div.container.mx-auto', 'block-pqr');

    expect($opening)->toBe('<section data-block="block-pqr" id="hero" class="py-20"><div class="container mx-auto">');
    expect($closing)->toBe('</div></section>');
});

test('escapes special characters in block id', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('div', 'block-"123"');

    expect($opening)->toContain('data-block="block-&quot;123&quot;"');
});

test('escapes HTML entities in block id', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('div', 'block-<test>');

    expect($opening)->toContain('data-block="block-&lt;test&gt;"');
});

test('injects data-block into first tag only', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('section>div>article', 'block-stu');

    expect($opening)->toBe('<section data-block="block-stu"><div><article>');
    expect($closing)->toBe('</article></div></section>');

    // Ensure only one data-block attribute
    expect(substr_count($opening, 'data-block'))->toBe(1);
});

test('compiles wrapper with different HTML tags', function () {
    [$opening1, $closing1] = WrapperCompiler::compileWrapper('header', 'block-1');
    expect($opening1)->toBe('<header data-block="block-1">');
    expect($closing1)->toBe('</header>');

    [$opening2, $closing2] = WrapperCompiler::compileWrapper('footer', 'block-2');
    expect($opening2)->toBe('<footer data-block="block-2">');
    expect($closing2)->toBe('</footer>');

    [$opening3, $closing3] = WrapperCompiler::compileWrapper('article', 'block-3');
    expect($opening3)->toBe('<article data-block="block-3">');
    expect($closing3)->toBe('</article>');
});

test('compiles wrapper with tailwind classes', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('div.flex.items-center.justify-between.px-4.py-2', 'block-vwx');

    expect($opening)->toBe('<div data-block="block-vwx" class="flex items-center justify-between px-4 py-2">');
    expect($closing)->toBe('</div>');
});

test('compiles wrapper with pseudo-class utilities', function () {
    [$opening, $closing] = WrapperCompiler::compileWrapper('div.hover:bg-blue-500.focus:ring-2', 'block-yza');

    expect($opening)->toBe('<div data-block="block-yza" class="hover:bg-blue-500 focus:ring-2">');
    expect($closing)->toBe('</div>');
});
