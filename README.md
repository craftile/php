# Craftile PHP Monorepo

A PHP monorepo using `symplify/monorepo-builder` with two packages:

## Packages

- **core** (`packages/core`) - Core functionality package
- **laravel** (`packages/laravel`) - Laravel integration package

## Setup

Install dependencies:
```bash
composer install
```

## Package Structure

```
/
├── packages/
│   ├── core/
│   │   ├── src/
│   │   ├── tests/
│   │   └── composer.json
│   └── laravel/
│       ├── src/
│       ├── tests/
│       └── composer.json
├── .github/workflows/split_packages.yml
├── monorepo-builder.php
└── composer.json
```

## Split Packages

This monorepo automatically splits packages into separate repositories:

- **craftile/core** → [craftile/core](https://github.com/craftile/core-php)
- **craftile/laravel** → [craftile/laravel](https://github.com/craftile/laravel)

Each package can be installed independently:

```bash
composer require craftile/core
composer require craftile/laravel
```
