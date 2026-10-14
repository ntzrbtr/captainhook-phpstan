# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0]

### Added

- `AnalyseStagedFiles` action for the `pre-commit` hook: runs PHPStan on staged PHP files
  covered by the project's PHPStan configuration (resolved via `phpstan dump-parameters`)
- Options `phpstan`, `configuration` and `memory-limit`

[Unreleased]: https://github.com/ntzrbtr/captainhook-phpstan/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/ntzrbtr/captainhook-phpstan/releases/tag/v1.0.0
