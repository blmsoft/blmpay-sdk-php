<?php
namespace BLMSoft\BLMPay;

final class Client
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeoutSeconds;
    private ?string $integrationOrigin;
    private ?string $hmacSecret;
    private string $apiVersion;

    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
        int $timeoutSeconds = 30,
        ?string $integrationOrigin = null,
        ?string $hmacSecret = null,
        ?string $apiVersion = null
    ) {
        $this->apiKey = trim($apiKey);
        if ($this->apiKey === '') {
            throw new \InvalidArgumentException('BLMPay API key is required.');
        }

        $this->hmacSecret = ($hmacSecret !== null && trim($hmacSecret) !== '') ? trim($hmacSecret) : null;
        $this->apiVersion = strtolower(trim($apiVersion ?: ($this->hmacSecret !== null ? 'v2' : 'v1')));
        if (!in_array($this->apiVersion, ['v1','v2'], true)) {
            throw new \InvalidArgumentException('BLMPay apiVersion must be v1 or v2.');
        }
        if ($this->apiVersion === 'v2' && $this->hmacSecret === null) {
            throw new \InvalidArgumentException('BLMPay HMAC secret is required for API v2.');
        }

        $this->baseUrl = rtrim($baseUrl ?: 'https://pay.blmtec.co.tz/api/' . $this->apiVersion, '/');
        $this->timeoutSeconds = $timeoutSeconds;
        $this->integrationOrigin = $this->normalizeIntegrationOrigin($integrationOrigin);
    }

    public function setIntegrationOrigin(?string $integrationOrigin): self
    {
        $this->integrationOrigin = $this->normalizeIntegrationOrigin($integrationOrigin);
        return $this;
    }

    public function listPayments(array $query = []): array { return $this->request('GET','/payments',null,$query); }
    public function getPayment(string $reference): array { return $this->request('GET','/payments/'.rawurlencode($reference)); }
    public function createPayment(array $payload, ?string $idempotencyKey = null): array { return $this->request('POST','/payments',$payload,[],$idempotencyKey); }
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
        $method = strtoupper($method);
        $url = $this->baseUrl . '/' . ltrim($path,'/');
        if ($query) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $rawBody = $payload !== null
            ? json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)
            : '';

        $headers = ['Accept: application/json'];
        if ($this->apiVersion === 'v2') {
            $timestamp = (string)time();
            $nonce = bin2hex(random_bytes(24));
            $parts = parse_url($url);
            $target = (string)($parts['path'] ?? '/');
            if (!empty($parts['query'])) {
                $target .= '?' . $parts['query'];
            }
            $canonical = $method . "\n"
                . $target . "\n"
                . $timestamp . "\n"
                . $nonce . "\n"
                . hash('sha256', $rawBody);
            $signature = hash_hmac('sha256', $canonical, (string)$this->hmacSecret);

            $headers[] = 'Authorization: BLMPay-HMAC ' . $this->apiKey;
            $headers[] = 'X-BLMPay-Timestamp: ' . $timestamp;
            $headers[] = 'X-BLMPay-Nonce: ' . $nonce;
            $headers[] = 'X-BLMPay-Signature: ' . $signature;
        } else {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        if ($this->integrationOrigin !== null) {
            $headers[] = 'X-BLMPay-Origin: ' . $this->integrationOrigin;
        }
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $headers[] = 'Idempotency-Key: ' . trim($idempotencyKey);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_CUSTOMREQUEST=>$method,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CONNECTTIMEOUT=>10,
            CURLOPT_TIMEOUT=>$this->timeoutSeconds,
            CURLOPT_HTTPHEADER=>$headers
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
        }

        $raw = (string)curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError !== '') {
            throw new Exception('BLMPay connection failed.',0,'connection_error',['detail'=>$curlError]);
        }
        $data = json_decode($raw,true);
        if (!is_array($data)) {
            throw new Exception('BLMPay returned an invalid JSON response.',$status,'invalid_response',['raw'=>$raw]);
        }
        if ($status < 200 || $status >= 300) {
            throw new Exception((string)($data['message']??'BLMPay request failed.'),$status,$data['error_code']??null,$data);
        }
        return $data;
    }

    private function normalizeIntegrationOrigin(?string $origin): ?string
    {
        $origin = trim((string)$origin);
        if ($origin === '') return null;
        if (!preg_match('#^https://#i', $origin)) {
            throw new \InvalidArgumentException('BLMPay integrationOrigin must be a full HTTPS URL, for example https://example.com.');
        }
        $parts = parse_url($origin);
        if (!is_array($parts) || empty($parts['host'])) {
            throw new \InvalidArgumentException('BLMPay integrationOrigin is not a valid URL.');
        }
        return $origin;
    }
}
