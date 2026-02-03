# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-01-22

### Added

- Initial release of Priority Icons plugin
- Signal-based CSS/JS injection via `apps.scp`
- Visual priority indicators for Emergency, High, Normal, Low
- Color-coded icons with Bootstrap-inspired palette
- CSS-only tooltips showing priority name on hover
- Pulsing animation for Emergency tickets
- Admin configuration for colors and styles
- Three icon styles: Circle, Badge, Dot
- PJAX-compatible DOM scanning
- MutationObserver for dynamic content
- Debounced replacements for performance
- Cache-busting via file modification time
- Screen reader accessibility support
- `prefers-reduced-motion` support
- Print stylesheet support
- Dark mode preparation
- Comprehensive PHPUnit test suite (77 tests)
- Full documentation (README, ARCHITECTURE, CODE_REVIEW)

### Security

- XSS prevention via `Format::htmlchars()` and `JSON_HEX_TAG`
- PHP execution blocked in assets directory
- No database interactions
- No user input processing

### Technical

- PHP 8.1+ with strict types
- ES6+ JavaScript with IIFE pattern
- CSS Custom Properties for theming
- BEM naming convention
- TreeWalker API for efficient DOM scanning

[Unreleased]: https://github.com/markus-michalski/osticket-priority-icons/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/markus-michalski/osticket-prioritiy-icons/releases/tag/v1.0.0
