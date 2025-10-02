<?php

use Craftile\Laravel\View\SimpleEmmetParser;

test('parses basic tag', function () {
    $result = SimpleEmmetParser::parse('div');
    expect($result)->toBe('<div></div>');
});

test('parses tag with id', function () {
    $result = SimpleEmmetParser::parse('div#header');
    expect($result)->toBe('<div id="header"></div>');
});

test('parses tag with single class', function () {
    $result = SimpleEmmetParser::parse('div.container');
    expect($result)->toBe('<div class="container"></div>');
});

test('parses tag with multiple classes', function () {
    $result = SimpleEmmetParser::parse('div.container.box');
    expect($result)->toBe('<div class="container box"></div>');
});

test('parses tag with classes containing special characters', function () {
    $result = SimpleEmmetParser::parse('div.py-20.bg-gray-100');
    expect($result)->toBe('<div class="py-20 bg-gray-100"></div>');
});

test('parses tag with classes containing colons', function () {
    $result = SimpleEmmetParser::parse('div.hover:bg-blue-500');
    expect($result)->toBe('<div class="hover:bg-blue-500"></div>');
});

test('parses tag with id and class', function () {
    $result = SimpleEmmetParser::parse('div#header.container');
    expect($result)->toBe('<div id="header" class="container"></div>');
});

test('parses tag with id and multiple classes', function () {
    $result = SimpleEmmetParser::parse('div#hero.container.box.py-20');
    expect($result)->toBe('<div id="hero" class="container box py-20"></div>');
});

test('parses tag with text content', function () {
    $result = SimpleEmmetParser::parse('div{Hello}');
    expect($result)->toBe('<div>Hello</div>');
});

test('parses tag with id and text content', function () {
    $result = SimpleEmmetParser::parse('div#header{Welcome}');
    expect($result)->toBe('<div id="header">Welcome</div>');
});

test('parses tag with class and text content', function () {
    $result = SimpleEmmetParser::parse('div.container{Hello World}');
    expect($result)->toBe('<div class="container">Hello World</div>');
});

test('parses tag with id, class, and text content', function () {
    $result = SimpleEmmetParser::parse('div#header.container{Welcome}');
    expect($result)->toBe('<div id="header" class="container">Welcome</div>');
});

test('parses nested tags', function () {
    $result = SimpleEmmetParser::parse('div>header');
    expect($result)->toBe('<div><header></header></div>');
});

test('parses nested tags with classes', function () {
    $result = SimpleEmmetParser::parse('div.container>header.main');
    expect($result)->toBe('<div class="container"><header class="main"></header></div>');
});

test('parses nested tags with id and class', function () {
    $result = SimpleEmmetParser::parse('div#wrapper.container>header#main.header');
    expect($result)->toBe('<div id="wrapper" class="container"><header id="main" class="header"></header></div>');
});

test('parses nested tags with content', function () {
    $result = SimpleEmmetParser::parse('div.container>header#main{Welcome}');
    expect($result)->toBe('<div class="container"><header id="main">Welcome</header></div>');
});

test('parses deeply nested tags', function () {
    $result = SimpleEmmetParser::parse('div>section>article');
    expect($result)->toBe('<div><section><article></article></section></div>');
});

test('parses deeply nested tags with mixed attributes', function () {
    $result = SimpleEmmetParser::parse('div.wrapper>section#hero.container>article.content{Text}');
    expect($result)->toBe('<div class="wrapper"><section id="hero" class="container"><article class="content">Text</article></section></div>');
});

test('parses tag with __content__ placeholder', function () {
    $result = SimpleEmmetParser::parse('div{__content__}');
    expect($result)->toBe('<div>__content__</div>');
});

test('parses nested tag with __content__ placeholder', function () {
    $result = SimpleEmmetParser::parse('section.wrapper>div.content{__content__}');
    expect($result)->toBe('<section class="wrapper"><div class="content">__content__</div></section>');
});

test('parses various HTML tags', function () {
    expect(SimpleEmmetParser::parse('section'))->toBe('<section></section>');
    expect(SimpleEmmetParser::parse('header'))->toBe('<header></header>');
    expect(SimpleEmmetParser::parse('footer'))->toBe('<footer></footer>');
    expect(SimpleEmmetParser::parse('article'))->toBe('<article></article>');
    expect(SimpleEmmetParser::parse('nav'))->toBe('<nav></nav>');
    expect(SimpleEmmetParser::parse('aside'))->toBe('<aside></aside>');
    expect(SimpleEmmetParser::parse('main'))->toBe('<main></main>');
    expect(SimpleEmmetParser::parse('span'))->toBe('<span></span>');
});

test('parses tag with hyphenated id', function () {
    $result = SimpleEmmetParser::parse('div#my-header');
    expect($result)->toBe('<div id="my-header"></div>');
});

test('parses tag with underscored id', function () {
    $result = SimpleEmmetParser::parse('div#my_header');
    expect($result)->toBe('<div id="my_header"></div>');
});

test('handles empty parts gracefully', function () {
    $result = SimpleEmmetParser::parse('div>');
    expect($result)->toBe('<div></div>');
});

test('parses complex real-world example', function () {
    $result = SimpleEmmetParser::parse('section#hero.py-20.bg-gray-100>div.container.mx-auto{__content__}');
    expect($result)->toBe('<section id="hero" class="py-20 bg-gray-100"><div class="container mx-auto">__content__</div></section>');
});
