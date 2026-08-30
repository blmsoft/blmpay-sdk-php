# BLMPay PHP SDK

Official PHP SDK for BLMPay API v1.

## Installation with Composer

Once the package is registered on Packagist, install it with:

```bash
composer require blmsoft/blmpay-php
```

Then load Composer's autoloader:

```php
require __DIR__ . '/vendor/autoload.php';

use BLMSoft\BLMPay\Client;

$blmpay = new Client(getenv('BLMPAY_API_KEY'));
$balance = $blmpay->getBalance();
```

### Install directly from GitHub before Packagist publication

```bash
composer config repositories.blmpay vcs https://github.com/Winnie7676/blmpay-sdk-php
composer require blmsoft/blmpay-php:dev-main
```

## Without Composer

You can also copy the SDK into your project and use the included standalone autoloader:

```php
require __DIR__ . '/vendor/blmpay-sdk/autoload.php';
$blmpay = new BLMSoft\BLMPay\Client(getenv('BLMPAY_API_KEY'));
```

## Example: create a payment

```php
use BLMSoft\BLMPay\Client;

$blmpay = new Client(getenv('BLMPAY_API_KEY'));

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

The SDK covers BLMPay API v1 for payments, balance, payment links, invoices, payouts, payout fee quotes, supported banks, recipient name lookup, webhooks, USDT activation, USDT address, transfers and withdrawals.

## Security

Never place a live BLMPay secret key in source control, browser JavaScript, a public Flutter application, or a public repository. Keep merchant secret keys server-side and use a unique idempotency key for each new payment or payout attempt.

## Links

- BLMPay OpenAPI: https://github.com/Winnie7676/blmpay-openapi
- BLMPay MCP: https://github.com/Winnie7676/blmpay-mcp

MIT License. Powered by BLMSoft.
