<?php

class Stripe
{
    private const API_BASE_URL = 'https://api.stripe.com/v1';

    public static function createCheckoutSession(array $params): array
    {
        return self::request('POST', '/checkout/sessions', $params);
    }

    public static function retrieveCheckoutSession(string $sessionId): array
    {
        return self::request('GET', '/checkout/sessions/' . rawurlencode($sessionId));
    }

    private static function request(string $method, string $path, array $params = []): array
    {
        $config = require __DIR__ . '/../config/stripe.php';
        if (empty($config['secret_key'])) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        // CA bundle resolution order:
        //   1. STRIPE_CA_BUNDLE from .env (explicit override, use on live hosts)
        //   2. curl.cainfo from php.ini
        //   3. config/cacert.pem shipped with the project
        // WAMP's PHP builds often have no system CA store, so without one of
        // these every request fails with "unable to get local issuer
        // certificate" (curl errno 60). The project bundle is checked last so
        // it works even when php.ini has not been reloaded by Apache.
        $caBundle = getenv('STRIPE_CA_BUNDLE') ?: ini_get('curl.cainfo');
        if ($caBundle === false || $caBundle === '' || !is_file($caBundle)) {
            $projectBundle = dirname(__DIR__) . '/config/cacert.pem';
            if (is_file($projectBundle)) {
                $caBundle = $projectBundle;
            }
        }

        $curl = curl_init(self::API_BASE_URL . $path);
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $config['secret_key'] . ':',
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if ($caBundle !== '' && is_file($caBundle)) {
            $curlOptions[CURLOPT_CAINFO] = $caBundle;
        }

        curl_setopt_array($curl, $curlOptions);

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $responseBody = curl_exec($curl);
        if ($responseBody === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('Stripe connection failed: ' . $error);
        }

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $response = json_decode($responseBody, true);

        if (!is_array($response)) {
            throw new RuntimeException('Stripe returned an invalid response.');
        }
        if ($statusCode < 200 || $statusCode >= 300 || isset($response['error'])) {
            throw new RuntimeException($response['error']['message'] ?? 'Stripe request failed.');
        }

        return $response;
    }
}
