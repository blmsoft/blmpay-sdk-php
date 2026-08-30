# BLMPay PHP SDK

Official PHP SDK foundation for BLMPay API v1. Version 0.1.0.

## Without Composer

Copy this folder into your project, for example `vendor/blmpay-sdk/`, then:

```php
require __DIR__ . '/vendor/blmpay-sdk/autoload.php';
$blmpay = new BLMSoft\BLMPay\Client(getenv('BLMPAY_API_KEY'));
$balance = $blmpay->getBalance();
```

## Composer-ready

The package includes `composer.json` and PSR-4 autoloading so it can later be published as `blmsoft/blmpay-php`.

## Covered API v1 endpoints

Payments, balance, payment links, invoices, payouts/fees/banks/name lookup, webhooks and USDT activation/address/transfers/withdrawals.

Never put a live API key in source control. Use environment variables and idempotency keys for payment/payout retries.

Powered by BLMSoft.
