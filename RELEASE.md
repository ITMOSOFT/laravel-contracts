# Release instructions

- Repository: `ITMOSOFT/laravel-contracts`
- Composer package: `itmosoft/laravel-contracts`
- Initial tag: `v1.0.0`

## Validate locally

```bash
composer validate --strict
composer install
composer test
```

## User-run Git commands

Run these yourself after local tests pass and GitHub Actions succeeds:

```bash
git add .
git commit -m "chore: prepare contracts v1.0.0"
git push origin main
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

## GitHub Release

Title: `v1.0.0 — Initial Release`

Description:

```markdown
## Highlights

- Polymorphic contract owner and counterparty.
- Contract reference, title, description, value and currency.
- Draft, pending, active, expired, terminated and rejected states.
- Effective and expiry dates.
- Renewal and termination tracking.
- Reusable contract clauses with ordering and metadata.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

composer require itmosoft/laravel-contracts
php artisan migrate

```

## Packagist

Submit the public repository at https://packagist.org/packages/submit and verify that Packagist detects `v1.0.0`.
