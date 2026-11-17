# ITMOSOFT Laravel Contracts

Commercial agreement and contract lifecycle management for Laravel applications, with polymorphic parties, clauses, renewals, and termination tracking.

## Features

- Polymorphic contract owner and counterparty
- Contract reference, title, description, value and currency
- Draft, pending, active, expired, terminated and rejected states
- Effective and expiry dates
- Renewal and termination tracking
- Reusable contract clauses with ordering and metadata

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require itmosoft/laravel-contracts
php artisan vendor:publish --tag=contracts-config
php artisan migrate
```

## Usage

Use the package's Eloquent model under `ITMOSOFT\\Contracts\\Models\\Contract`. Create records with the required polymorphic relation fields and use the model lifecycle methods documented below.

### Lifecycle methods

- `activate()` activates draft/pending contracts.
- `terminate($reason)` terminates active/pending contracts.
- `renew($attributes)` creates a separate draft renewal, links it to the source contract, and copies clauses.

## Important boundaries

This package provides persistence models and lifecycle primitives. It does not provide authentication, authorization, approval routing, notifications, file uploads, electronic signatures, payment processing, or tenant isolation. Enforce permissions and business-specific validation in the host application. File paths and document metadata are references only; implement secure upload, storage, access control, and malware scanning in the application.

## Testing

```bash
composer install
composer test
```

## License

MIT.
