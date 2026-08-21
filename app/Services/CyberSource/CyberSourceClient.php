<?php

namespace App\Services\CyberSource;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Signs and sends CyberSource REST API requests using JWT authentication with
 * a shared-secret key — the migration path CyberSource recommends over the
 * HTTP Signature scheme it's deprecating (sunset March 2027). See:
 * https://developer.cybersource.com/docs/cybs/en-us/platform/developer/all/rest/rest-getting-started/restgs-jwt-shared-secret-intro/restgs-jwt-con-shared-secret-intro.html
 */
class CyberSourceClient
{
    private string $merchantId;

    private string $keyId;

    private string $secretKey;

    private string $host;

    public function __construct()
    {
        $this->merchantId = (string) config('payment.cybersource.merchant_id');
        $this->keyId = (string) config('payment.cybersource.key_id');
        $this->secretKey = (string) config('payment.cybersource.secret_key');
        $this->host = (string) config('payment.cybersource.run_environment');
    }

    public function post(string $resourcePath, array $payload): Response
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->signRequest('post', $resourcePath, $body),
            'v-c-merchant-id' => $this->merchantId,
        ])
            ->timeout(20)
            ->withBody($body, 'application/json')
            ->post("https://{$this->host}{$resourcePath}");
    }

    /**
     * Builds the HS256 JWT CyberSource expects in the Authorization header.
     * The shared secret CyberSource issues is itself base64-encoded, so it
     * must be decoded before use as the raw HMAC signing key — signing with
     * the encoded string directly produces a signature CyberSource rejects.
     */
    private function signRequest(string $method, string $resourcePath, string $body): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
            'kid' => $this->keyId,
        ];

        $claims = [
            'iat' => time(),
            'exp' => time() + 120,
            'request-method' => $method,
            'request-resource-path' => $resourcePath,
            'request-host' => $this->host,
            'iss' => $this->merchantId,
            'jti' => (string) Str::uuid(),
            'v-c-jwt-version' => '2',
            'v-c-merchant-id' => $this->merchantId,
            'digest' => base64_encode(hash('sha256', $body, true)),
            'digestAlgorithm' => 'SHA-256',
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($claims)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), base64_decode($this->secretKey), true);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
