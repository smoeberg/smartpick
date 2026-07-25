<?php
/**
 * ShipmondoAPI - Integration til Shipmondo REST API v3
 */
class ShipmondoAPI
{
    private $apiUser;
    private $apiKey;
    private $baseUrl = 'https://app.shipmondo.com/api/public/v3/';

    public function __construct($apiUser, $apiKey)
    {
        $this->apiUser = trim($apiUser);
        $this->apiKey = trim($apiKey);
    }

    private function request($endpoint, $method = 'GET', $data = null)
    {
        $url = $this->baseUrl . ltrim($endpoint, '/');
        $curl = curl_init($url);

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->apiUser . ':' . $this->apiKey,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CUSTOMREQUEST => strtoupper($method)
        ];

        if ($data !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $opts);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return ['success' => false, 'error' => 'cURL fejl: ' . $error];
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'http_code' => $httpCode,
                'error' => isset($decoded['message']) ? $decoded['message'] : 'HTTP fejl ' . $httpCode,
                'response' => $decoded
            ];
        }

        return [
            'success' => true,
            'http_code' => $httpCode,
            'data' => $decoded
        ];
    }

    /**
     * Test forbindelse til Shipmondo
     */
    public function testConnection()
    {
        return $this->request('account/');
    }

    /**
     * Hent kontooplysninger
     */
    public function getAccount()
    {
        return $this->request('account/');
    }

    /**
     * Hent forsendelsesskabeloner (shipment templates)
     */
    public function getShipmentTemplates()
    {
        return $this->request('shipment_templates/');
    }

    /**
     * Hent printkøer / printere
     */
    public function getPrintQueues()
    {
        return $this->request('print_queues/');
    }

    /**
     * Opret forsendelse og generer fragtlabel
     *
     * @param array $payload Shipmondo v3 shipment payload
     */
    public function createShipment($payload)
    {
        return $this->request('shipments/', 'POST', $payload);
    }

    /**
     * Hent forsendelsesdetaljer
     */
    public function getShipment($shipmentId)
    {
        return $this->request('shipments/' . intval($shipmentId));
    }
}
