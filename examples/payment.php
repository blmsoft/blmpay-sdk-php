<?php
require __DIR__.'/../autoload.php';
use BLMSoft\BLMPay\Client;

$blmpay = new Client(
    getenv('BLMPAY_API_KEY'),
    'https://pay.blmtec.co.tz/api/v1',
    30,
    getenv('BLMPAY_INTEGRATION_ORIGIN') ?: null
);

$payment = $blmpay->createPayment([
    'payment_type'=>'mobile',
    'phone_number'=>'255712345678',
    'customer_name'=>'Demo Customer',
    'details'=>['amount'=>5000,'currency'=>'TZS'],
    'metadata'=>['order_id'=>'ORDER-1001'],
], 'order-1001-payment');

print_r($payment);
