# BLMPay PHP SDK

> **API V1 deprecation notice:** BLMPay API v1 is legacy and is scheduled to be retired on **15 November 2026 at 23:59 EAT**. New integrations must use **API v2 HMAC**. Migrate before the retirement date.

Official PHP SDK for BLMPay API v2.

## Installation with Composer

Once the package is registered on Packagist, install it with:

```bash
composer require blmsoft/blmpay-php
```

Then load Composer's autoloader:

```php
require __DIR__ . '/vendor/autoload.php';

use BLMSoft\BLMPay\Client;

$blmpay = new Client(
    getenv('BLMPAY_API_KEY'),
    null,
    30,
    getenv('BLMPAY_INTEGRATION_ORIGIN') ?: null,
    getenv('BLMPAY_API_SECRET')
);

$balance = $blmpay->getBalance();
```

### Install directly from GitHub before Packagist publication

```bash
composer config repositories.blmpay vcs https://github.com/blmsoft/blmpay-sdk-php
composer require blmsoft/blmpay-php:dev-main
```

## Without Composer

```php
require __DIR__ . '/vendor/blmpay-sdk/autoload.php';

$blmpay = new BLMSoft\BLMPay\Client(
    getenv('BLMPAY_API_KEY'),
    null,
    30,
    getenv('BLMPAY_INTEGRATION_ORIGIN') ?: null,
    getenv('BLMPAY_API_SECRET')
);
```

## Domain-bound API keys

New BLMPay API keys can be restricted to one HTTPS integration domain. Pass the exact HTTPS integration URL saved on the key as the fourth constructor argument, or call:

```php
$blmpay->setIntegrationOrigin('https://example.com');
```

The SDK then automatically sends:

```http
X-BLMPay-Origin: https://example.com
```

A mismatched domain can be rejected with `403 integration_domain_not_allowed`. A restricted key with no origin can return `403 integration_origin_required`.

Existing legacy API keys without a configured domain remain backward compatible and can continue using the SDK without this option.

## Example: create a payment

```php
use BLMSoft\BLMPay\Client;

$blmpay = new Client(
    getenv('BLMPAY_API_KEY'),
    null,
    30,
    'https://example.com',
    getenv('BLMPAY_API_SECRET')
);

$payment = $blmpay->createPayment([
    'payment_type' => 'mobile',
    'details' => [
        'amount' => 5000,
        'currency' => 'TZS',
    ],
    'phone_number' => '255712345678',
    'customer' => [
        'firstname' => 'Juma',
        'lastname' => 'Mwangi',
        'email' => 'juma@example.com',
    ],
    'webhook_url' => 'https://example.com/webhooks/blmpay',
], 'order-001-attempt-1');
```

## API coverage

The SDK covers BLMPay API v2 for payments, balance, payment links, invoices, payouts, payout fee quotes, supported banks, recipient name lookup, webhooks, USDT activation, USDT address, transfers and withdrawals.

## API v2 HMAC authentication

API v2 signs each request with the public API key plus the HMAC secret. The SDK automatically sends `Authorization: BLMPay-HMAC <PUBLIC_API_KEY>`, `X-BLMPay-Timestamp`, `X-BLMPay-Nonce`, and `X-BLMPay-Signature`.

The canonical string is:

```text
METHOD
REQUEST_TARGET
TIMESTAMP
NONCE
SHA256(RAW_BODY)
```

Restricted-domain checks remain enabled as a secondary security layer.

### Legacy API v1

Existing Bearer-key integrations may keep using v1 during the migration window, but API v1 is scheduled for retirement on **15 November 2026 at 23:59 EAT**. For temporary v1 use, construct the client with `apiVersion: 'v1'` and no HMAC secret. New integrations must use v2.

## Security

Never place a live BLMPay API key in source control, browser JavaScript, a public Flutter application, APK/IPA or a public repository. Keep privileged merchant credentials server-side and use a unique idempotency key for each new payment or payout attempt.

## Links

- BLMPay OpenAPI: https://github.com/blmsoft/blmpay-openapi
- BLMPay MCP: https://github.com/blmsoft/blmpay-mcp

MIT License. Powered by BLMSoft.
