# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

## [1.0.4] - 2025-02-13

### Added
- New `exists()` method added for checking the record(s) existancy after execute the query 
- You can easily check whether the query contains record(s) or the query result is empty 

### Changed
- Migrated all loose comparisons (==) to strict comparisons (===)

### Fixed
- Remaining loose comparison operators in query builder

## [1.0.3] - 2025-02-13

### Changed
- Started strict comparison migration (incomplete - use v1.0.4 instead)

## [1.0.2] - 2025-10-04

### Fixed
- Minor bug fixes
- Code improvements

## [1.0.1] - 2025-10-04

### Changed
- Initial stable release improvements

## [1.0] - 2025-10-03

### Added
- Initial release
- Fluent query builder interface
- Support for SELECT, INSERT, UPDATE, DELETE
- WHERE clauses with multiple conditions
- JOIN support
- Prepared statements for security

[Unreleased]: https://github.com/buildQL/query-builder/compare/v1.0.4...HEAD
[1.0.4]: https://github.com/buildQL/query-builder/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/buildQL/query-builder/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/buildQL/query-builder/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/buildQL/query-builder/compare/v1.0.0...v1.0.1
[1.0]: https://github.com/buildQL/query-builder/releases/tag/v1.0