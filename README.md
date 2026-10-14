# ntzrbtr/captainhook-phpstan

[![Tests](https://github.com/ntzrbtr/captainhook-phpstan/actions/workflows/tests.yml/badge.svg)](https://github.com/ntzrbtr/captainhook-phpstan/actions/workflows/tests.yml)
[![Latest version](https://img.shields.io/packagist/v/ntzrbtr/captainhook-phpstan)](https://packagist.org/packages/ntzrbtr/captainhook-phpstan)
[![License](https://img.shields.io/github/license/ntzrbtr/captainhook-phpstan)](LICENSE)

CaptainHook action for the `pre-commit` hook that runs PHPStan only on staged PHP files
the project's PHPStan configuration actually covers.

The action asks PHPStan itself for the resolved configuration (`phpstan dump-parameters --json`),
so `paths`, `excludePaths` (including wildcards), `includes` and placeholders such as
`%currentWorkingDirectory%` behave exactly as in a full `phpstan analyse` run. No per-project
directory lists are needed in `captainhook.json`.

## Installation

```bash
composer require --dev ntzrbtr/captainhook-phpstan
```

## Configuration

```json
{
    "pre-commit": {
        "enabled": true,
        "actions": [
            {
                "action": "\\Netzarbeiter\\CaptainHookPhpstan\\Action\\AnalyseStagedFiles"
            }
        ]
    }
}
```

Optional settings:

```json
{
    "action": "\\Netzarbeiter\\CaptainHookPhpstan\\Action\\AnalyseStagedFiles",
    "options": {
        "phpstan": "vendor/bin/phpstan",
        "configuration": "phpstan.neon",
        "memory-limit": "1G"
    }
}
```

| Option | Default | Meaning |
|---|---|---|
| `phpstan` | `vendor/bin/phpstan` | PHPStan executable |
| `configuration` | PHPStan's own lookup | Passed as `--configuration` |
| `memory-limit` | PHPStan default | Passed as `--memory-limit` |

## Behaviour

- Considers added, copied, modified and renamed `*.php` files in the index.
- Does nothing if none of them is covered by `paths` or all are excluded.
- Fails the commit with PHPStan's output if errors are found.
- Note: PHPStan analyses the files in the working tree, not the staged content.
  Partially staged files are checked in their current working tree state.

## Requirements

- PHP 7.4 or newer
- CaptainHook 5.18 or newer
- PHPStan 2.x, or a 1.x release that has the `dump-parameters` command

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

## Releasing

1. Move the entries under `[Unreleased]` in `CHANGELOG.md` to a new version section.
2. Commit, then tag and push: `git tag v1.2.3 && git push origin v1.2.3`
3. The release workflow creates the GitHub release with the changelog entry as notes;
   Packagist picks up the new tag automatically.

## License

MIT, see [LICENSE](LICENSE).
