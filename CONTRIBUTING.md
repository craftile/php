# Contributing to Craftile PHP

Thank you for considering contributing to Craftile PHP! This document outlines the process for contributing to this monorepo.

## Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/craftile/php.git
   cd craftile-php
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

   *Git hooks are automatically installed via composer scripts - no manual setup required!*

## Code Standards

### Commit Messages

We use [Conventional Commits](https://conventionalcommits.org/) for all commit messages:

- `feat:` - A new feature
- `fix:` - A bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes (formatting, etc.)
- `refactor:` - Code refactoring
- `perf:` - Performance improvements
- `test:` - Adding or updating tests
- `chore:` - Maintenance tasks

**Breaking changes** should use `!` (e.g., `feat!:` or `fix!:`) or include `BREAKING CHANGE:` in the footer.

### Code Quality

Before each commit, the following tools run automatically via git hooks:

- **Laravel Pint** - Code formatting
- **PHPStan** - Static analysis
- **PestPHP** - Tests (on push)

You can also run these manually:
```bash
composer format    # Format code
composer analyse   # Run static analysis
composer test      # Run tests
```

## Package Structure

This is a monorepo with the following packages:

- **`packages/core`** - Core functionality
- **`packages/laravel`** - Laravel integration (depends on core)

### Adding New Packages

1. Create package directory under `packages/`
2. Add `composer.json` with proper autoloading
3. Update root `composer.json` replace section
4. Add to monorepo split workflow if needed

## Testing

- Write tests for all new features and bug fixes
- Tests are located in each package's `tests/` directory
- We use PestPHP as our testing framework
- Run tests with `composer test`

## Pull Request Process

1. **Fork the repository** and create a feature branch
2. **Make your changes** following our coding standards
3. **Write tests** for your changes
4. **Ensure all checks pass** (formatting, analysis, tests)
5. **Submit a pull request** with:
   - Clear description of changes
   - Link to any related issues
   - Screenshots if applicable

## Releases

Releases are automated based on conventional commits:

- **Patch** (`fix:`, `perf:`, `refactor:`) → `v1.0.1`
- **Minor** (`feat:`) → `v1.1.0`
- **Major** (`feat!:`, `fix!:`, `BREAKING CHANGE:`) → `v2.0.0`

## Package Splitting

Packages are automatically split into separate repositories:

- `packages/core` → `craftile/core`
- `packages/laravel` → `craftile/laravel`

## Getting Help

- 📝 Open an issue for bugs or feature requests
- 💬 Start a discussion for questions
- 📖 Check existing documentation

## License

By contributing, you agree that your contributions will be licensed under the MIT License.