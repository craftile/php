<?php

declare(strict_types=1);

use Craftile\Laravel\Support\DirectiveVariants;

test('generates correct directive variants', function () {
    $variants = DirectiveVariants::generate('craftileBlock');

    expect($variants)->toContain('craftileBlock');
    expect($variants)->toContain('CraftileBlock');
    expect($variants)->toContain('craftile_block');
    expect($variants)->toContain('craftileblock');
    expect($variants)->toHaveCount(4);
});

test('generates correct directive variants for single word', function () {
    $variants = DirectiveVariants::generate('block');

    expect($variants)->toContain('block');
    expect($variants)->toContain('Block');
    expect($variants)->toContain('block'); // snake_case of 'block' is still 'block'
    expect($variants)->toHaveCount(2); // Only unique values
});

test('handles camelCase without underscores', function () {
    $variants = DirectiveVariants::generate('myDirective');

    expect($variants)->toContain('myDirective');
    expect($variants)->toContain('MyDirective');
    expect($variants)->toContain('my_directive');
    expect($variants)->toContain('mydirective');
    expect($variants)->toHaveCount(4);
});

test('handles single character input', function () {
    $variants = DirectiveVariants::generate('a');

    expect($variants)->toContain('a');
    expect($variants)->toContain('A');
    expect($variants)->toHaveCount(2);
});

test('returns unique variants only', function () {
    $variants = DirectiveVariants::generate('test');

    expect($variants)->toBe(array_unique($variants));
});

test('generates correct end directive variants', function () {
    $variants = DirectiveVariants::generateEnd('craftileContent');

    expect($variants)->toContain('endCraftileContent');     // camelCase -> endCamelCase
    expect($variants)->toContain('EndCraftileContent');     // PascalCase -> EndPascalCase
    expect($variants)->toContain('end_craftile_content');   // snake_case -> end_snake_case
    expect($variants)->toContain('endcraftilecontent');     // lowercase -> endlowercase
    expect($variants)->toHaveCount(4);
});

test('generates end variants for single word', function () {
    $variants = DirectiveVariants::generateEnd('content');

    expect($variants)->toContain('endcontent');    // lowercase -> endlowercase
    expect($variants)->toContain('EndContent');    // PascalCase -> EndPascalCase
    expect($variants)->toHaveCount(2); // Only unique values (no snake_case for single word)
});

test('end variants preserve case patterns', function () {
    $variants = DirectiveVariants::generateEnd('myDirective');

    expect($variants)->toContain('endMyDirective');        // camelCase -> endCamelCase
    expect($variants)->toContain('EndMyDirective');        // PascalCase -> EndPascalCase
    expect($variants)->toContain('end_my_directive');      // snake_case -> end_snake_case
    expect($variants)->toContain('endmydirective');        // lowercase -> endlowercase
});
