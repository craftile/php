# 🏗️ Craftile PHP

> **PHP integration for the Craftile visual editor** - Build dynamic, block-based layouts with seamless editor integration.

[![Latest Version](https://img.shields.io/github/v/release/craftile/php?style=flat-square)](https://github.com/craftile/php/releases)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue?style=flat-square)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)

Craftile PHP provides the server-side foundation for integrating with the [Craftile Visual Editor](https://github.com/craftile/editor), enabling you to build dynamic, block-based content management systems with Laravel.

## ✨ Features

- 🎨 **Visual Block Editor Integration** - Seamlessly connect with Craftile's drag-and-drop editor
- 🔧 **Laravel-First Design** - Built specifically for Laravel applications with service providers and facades
- 📦 **Block-Based Architecture** - Create reusable content blocks with properties and validation
- 🎯 **Template Compilation** - Transform JSON/YAML templates into optimized Blade views
- 🚀 **Performance Optimized** - Intelligent caching and lazy loading for production use
- 🔍 **Developer Friendly** - Comprehensive testing, static analysis, and clear documentation

## 🔌 Official Packages

This monorepo contains:

- **[craftile/core](https://github.com/craftile/core-php)** - Core block functionality and data structures
- **[craftile/laravel](https://github.com/craftile/laravel)** - Laravel integration with service providers and Blade directives

## 📁 Project Structure

```
packages/
├── core/           # Core functionality (framework-agnostic)
│   ├── src/        # Block schemas, data structures, services
│   └── tests/      # Unit tests
└── laravel/        # Laravel integration
    ├── src/        # Service providers, compilers, facades
    ├── config/     # Configuration files
    ├── resources/  # Blade templates and views
    └── tests/      # Integration tests
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

Craftile PHP is open-sourced software licensed under the [MIT license](LICENSE).

---

**Ready to build something amazing?** Check out the [Craftile Editor](https://github.com/craftile/editor) to see the visual editing experience in action! 🚀