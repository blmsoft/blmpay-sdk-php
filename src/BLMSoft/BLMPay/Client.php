<?php
namespace BLMSoft\BLMPay;

final class Client
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl = 'https://pay.blmtec.co.tz/api/v1',
        private int $timeoutSeconds = 30
    ) {
        $this->apiKey = trim($this->apiKey);
        $this->baseUrl = rtrim($this->baseUrl, '/');
        if ($this->apiKey === '') throw new \InvalidArgumentException('BLMPay API key is required.');
    }

    public function listPayments(array $query = []): array { return $this->request('GET','/payments',null,$query); }
    public function getPayment(string $reference): array { return $this->request('GET','/payments/'.rawurlencode($reference)); }
    public function createPayment(array $payload, ?string $idempotencyKey = null): array { return $this->request('POST','/payments',$payload,[], $idempotencyKey); }
    public function getBalance(): array { return $this->request('GET','/balance'); }
    public function listPaymentLinks(): array { return $this->request('GET','/payment-links'); }
    public function createPaymentLink(array $payload): array { return $this->request('POST','/payment-links',$payload); }
    public function listInvoices(): array { return $this->request('GET','/invoices'); }
    public function createInvoice(array $payload): array { return $this->request('POST','/invoices',$payload); }
    public function listPayouts(array $query = []): array { return $this->request('GET','/payouts',null,$query); }
    public function getPayout(string $reference): array { return $this->request('GET','/payouts/'.rawurlencode($reference)); }
    public function getPayoutFee(int $amount, string $channel='mobile'): array { return $this->request('GET','/payouts/fee',null,['amount'=>$amount,'channel'=>$channel]); }
    public function listPayoutBanks(): array { return $this->request('GET','/payouts/banks'); }
    public function lookupPayoutName(array $payload): array { return $this->request('POST','/payouts/name-lookup',$payload); }
    public function createPayout(array $payload, string $idempotencyKey): array { return $this->request('POST','/payouts/send',$payload,[],$idempotencyKey); }
    public function listWebhooks(): array { return $this->request('GET','/webhooks'); }
    public function createWebhook(array $payload): array { return $this->request('POST','/webhooks',$payload); }
    public function deleteWebhook(int $id): array { return $this->request('DELETE','/webhooks/'.$id); }
    public function activateUsdt(): array { return $this->request('POST','/usdt/activate',[]); }
    public function getUsdtAddress(): array { return $this->request('GET','/usdt/address'); }
    public function listUsdtTransfers(array $query=[]): array { return $this->request('GET','/usdt/transfers',null,$query); }
    public function quoteUsdtWithdrawal(string $address, string|int|float $amount, string $idempotencyKey): array { return $this->request('POST','/usdt/withdrawals/quote',['address'=>$address,'amount'=>$amount],[],$idempotencyKey); }
    public function confirmUsdtWithdrawal(string $quoteReference): array { return $this->request('POST','/usdt/withdrawals',['quote_reference'=>$quoteReference]); }

    public function request(string $method, string $path, ?array $payload=null, array $query=[], ?string $idempotencyKey=null): array
    {
        $url = $this->baseUrl . '/' . ltrim($path,'/');
        if ($query) $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $headers = ['Authorization: Bearer '.$this->apiKey, 'Accept: application/json'];
        if ($payload !== null) $headers[] = 'Content-Type: application/json';
        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') $headers[] = 'Idempotency-Key: '.trim($idempotencyKey);
        $ch = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>strtoupper($method),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>$this->timeoutSeconds,CURLOPT_HTTPHEADER=>$headers]);
        if ($payload !== null) curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $raw = (string)curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($curlError !== '') throw new Exception('BLMPay connection failed.',0,'connection_error',['detail'=>$curlError]);
        $data = json_decode($raw,true);
        if (!is_array($data)) throw new Exception('BLMPay returned an invalid JSON response.',$status,'invalid_response',['raw'=>$raw]);
        if ($status < 200 || $status >= 300) throw new Exception((string)($data['message']??'BLMPay request failed.'),$status,$data['error_code']??null,$data);
        return $data;
    }
}
