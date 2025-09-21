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

## Monorepo Commands

Merge package dependencies to root:
```bash
vendor/bin/monorepo-builder merge
```

Validate package versions:
```bash
vendor/bin/monorepo-builder validate
```

Propagate dependencies to packages:
```bash
vendor/bin/monorepo-builder propagate
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

- **craftile/core** → [craftile/core](https://github.com/craftile/core)
- **craftile/laravel** → [craftile/laravel](https://github.com/craftile/laravel)

Each package can be installed independently:

```bash
composer require craftile/core
composer require craftile/laravel
```

### Setup Split Repositories

1. Create the target repositories on GitHub:
   - `craftile/core`
   - `craftile/laravel`

2. Create a Personal Access Token with repo permissions:
   - Go to GitHub Settings → Developer settings → Personal access tokens
   - Generate new token with `repo` scope

3. Add the token as a repository secret:
   - Go to repository Settings → Secrets and variables → Actions
   - Add new secret named `ACCESS_TOKEN` with your token value

The workflow will automatically run on:
- Push to `main` branch
- New releases