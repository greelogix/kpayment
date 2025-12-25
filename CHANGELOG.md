# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- Complete KNET Payment Gateway integration
- Form-based payment processing with redirects
- Payment response validation with hash verification
- Admin panel for settings management (`/admin/kpayment/settings`)
- Site settings-driven configuration (database-first approach)
- Refund processing support
- KFAST (KNET Fast Payment) support
- Apple Pay integration support
- Payment status tracking with database models
- Default settings seeder
- Comprehensive error handling
- Laravel 10.x and 11.x compatibility
- Auto-discovery support
- Facade for easy access (`KPayment`)
- Database migrations for:
  - Site settings table
  - Payments table
  - Payment methods table
- Models with relationships:
  - `KnetPayment`
  - `PaymentMethod`
  - `SiteSetting`
- Events:
  - `PaymentStatusUpdated`
- Service methods:
  - `generatePaymentForm()`
  - `validateResponse()`
  - `processResponse()`
  - `processRefund()`
  - `getPaymentByTrackId()`
  - `getPaymentByTransId()`

### Security
- SHA-256 hash validation for responses
- Admin routes protected with authentication middleware
- CSRF protection (response routes exempt)
- Secure credential storage in database


