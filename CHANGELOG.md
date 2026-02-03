# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Nothing yet

### Changed
- Nothing yet

### Fixed
- Nothing yet

## [1.0.3] - 2026-02-03

### Changed
- rewrite tests for ob_start()-based asset injection
- add GitHub Actions CI pipeline and update .gitignore

### Fixed
- drop PHP 8.1 from matrix and disable coverage in CI
- use composer update for multi-PHP version compatibility

## [1.0.2] - 2026-02-03

### Added
- add priority color tint to table cell backgrounds

### Changed
- reset of version in plugin.php
- center priority icons vertically in table cells

### Fixed
- override osTicket inline background-color on inner div
- resolve infinite DOM recursion and config colors not applied
- use admin-configured colors instead of hardcoded defaults
- escape */ in docblock that prematurely closed the comment
- use ob_start() for asset injection instead of addExtraHeader
- replace Signal-based injection with inline addExtraHeader

## [1.0.1] - 2026-02-03

### Added
- Add German priority name support (Niedrig, Normal, Hoch, Notfall)

### Changed
- update changelog for unreleased changes and add version links

### Fixed
- remove class filter from Signal::connect causing handler to never fire
- Use dynamic plugin directory path instead of hardcoded
- Load config.php explicitly to make PriorityIconsConfig available
- Add isSingleton() and enable() methods for instance creation
- Remove enable() method - not needed for simple plugins
- Remove type declaration from config_class property

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
