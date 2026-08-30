<?php
namespace BLMSoft\BLMPay;

class Exception extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly ?string $errorCode = null,
        public readonly array $response = []
    ) { parent::__construct($message, $httpStatus); }
}
