<?php

namespace SmartPick\Domain\Shipping;

// File: /admin/custom/smartpick/class/ShipmondoPOC.class.php

/**
 * ShipmondoPOC - Klasse til test af API-forbindelse til Shipmondo v3
 */
class ShipmondoPOC
{
    private $user;
    private $key;
    private $endpoint = 'https://app.shipmondo.com/api/public/v3/account/';

    /**
     * Constructor
     *
     * @param string $user API brugernavn
     * @param string $key  API nøgle
     */
    public function __construct($user, $key)
    {
        $this->user = trim($user);
        $this->key = trim($key);
    }

    /**
     * Tester forbindelsen til Shipmondo API og returnerer kontodetaljer
     *
     * @return array Associativt array med enten kontodata eller fejl
     */
    public function testConnection()
    {
        $curl = curl_init($this->endpoint);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->user . ':' . $this->key,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return ['error' => 'cURL error: ' . $error];
        }

        $decoded = json_decode($response, true);

        if ($httpCode !== 200 || !is_array($decoded)) {
            return [
                'error' => 'HTTP ' . $httpCode,
                'response' => $response
            ];
        }

        return $decoded;
    }
}
