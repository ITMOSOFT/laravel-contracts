# ITMOSOFT Laravel Contracts

Commercial agreement and contract lifecycle management for Laravel applications, with polymorphic parties, clauses, renewals, and termination tracking.

## Features

- Polymorphic contract owner and counterparty
- Contract reference, title, description, value, currency, and metadata
- Draft, pending, active, expired, terminated, and rejected states
- Effective and expiry dates
- Transactional activation, termination, and renewal operations
- Row locking for lifecycle transitions and renewal creation
- Reusable contract clauses with ordering and metadata
- Automatic UUID reference generation when omitted

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require itmosoft/laravel-contracts
php artisan vendor:publish --tag=contracts-config
php artisan migrate
```

The service provider is registered through Laravel package auto-discovery.

## Usage

```php
use ITMOSOFT\Contracts\Models\Contract;

$contract = Contract::create([
    'owner_type' => App\Models\Company::class,
    'owner_id' => $company->id,
    'counterparty_type' => App\Models\Customer::class,
    'counterparty_id' => $customer->id,
    'title' => 'Master Services Agreement',
    'currency' => 'USD',
    'contract_value' => '12500.00',
    'effective_at' => '2026-12-01',
    'expires_at' => '2027-11-30',
]);

$contract->activate();
$contract->terminate('Agreement ended');

$renewal = $contract->renew([
    'title' => 'Master Services Agreement — Renewal',
    'effective_at' => '2027-12-01',
    'expires_at' => '2028-11-30',
]);
```

## Lifecycle behavior

- `activate()` changes `draft` or `pending` contracts to `active`.
- `terminate($reason)` changes `active` or `pending` contracts to `terminated`, recording the date and optional reason.
- `renew($attributes)` creates a separate `draft` contract from an `active` or `expired` contract, links it through `renewed_from_id`, increments `renewal_count`, and copies clauses.
- Lifecycle operations require a persisted contract and run inside database transactions. Row-level locking is used where supported by the database driver.
- Invalid state transitions return `false`; attempting to renew an ineligible contract throws `InvalidArgumentException`.

## Validation

The model enforces a non-empty title, supported status, three-letter uppercase currency code, non-negative `DECIMAL(14,2)` contract value, and valid effective/expiry date order. Clause titles and bodies are required. Metadata must be JSON encodable.

## Important boundaries

This package provides persistence models and lifecycle primitives. It does not provide authentication, authorization, approval routing, notifications, file uploads, electronic signatures, payment processing, or tenant isolation. Enforce permissions and business-specific validation in the host application. File paths and document metadata are references only; implement secure upload, storage, access control, and malware scanning in the application.

## Testing

```bash
composer install
composer validate --strict
composer test
```

## License

MIT.
