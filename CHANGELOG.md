# Changelog

## [1.0.1] - 2026-11-25

- Added contract and clause model validation for required fields, status, currency, monetary precision, date order, and JSON metadata.
- Added automatic UUID reference generation when omitted.
- Made activation, termination, and renewal operations transactional and row-locked.
- Rejected lifecycle operations on unsaved contracts.
- Expanded lifecycle, renewal, validation, and clause tests.
- Updated usage and lifecycle documentation.

## [1.0.0] - 2026-11-17

- Initial release of Laravel Contracts.
- Added core Eloquent models, migrations, lifecycle methods, tests, and Laravel auto-discovery.
