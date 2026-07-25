<?php
/**
 * SmartPickMistralAI - Integration til Mistral AI for WMS lageroptimering & slotting
 */
class SmartPickMistralAI
{
    private $apiKey;
    private $model;
    private $baseUrl = 'https://api.mistral.ai/v1/chat/completions';

    public function __construct($apiKey, $model = 'mistral-small-latest')
    {
        $this->apiKey = trim($apiKey);
        $this->model = !empty($model) ? trim($model) : 'mistral-small-latest';
    }

    /**
     * Send prompt til Mistral AI REST API
     */
    public function queryMistral($prompt, $systemInstruction = '')
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'Mistral AI API key ikke angivet i konfigurationen'];
        }

        $messages = [];
        if (!empty($systemInstruction)) {
            $messages[] = ['role' => 'system', 'content' => $systemInstruction];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.2
        ];

        $curl = curl_init($this->baseUrl);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return ['success' => false, 'error' => 'cURL fejl mod Mistral AI: ' . $error];
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($decoded['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'content' => $decoded['choices'][0]['message']['content'],
                'model' => $this->model
            ];
        }

        return [
            'success' => false,
            'http_code' => $httpCode,
            'error' => $decoded['message'] ?? 'Fejl ved svar fra Mistral AI'
        ];
    }

    /**
     * Generer AI-baseret slotting & placering af varer baseret på salgsdata og lagerafstand
     */
    public function generateSlottingOptimization($productsData)
    {
        $system = "Du er en AI-ekspert i WMS og lagerlogistik. Din opgave er at analysere historiske salgsfrekvenser for varer og foreslå den optimale placering (Rack / Bin) tæt på pakke-udgangsområdet for at minimere gangtid og forbedre arbejdsmiljøet.";
        
        $prompt = "Her er lagerets seneste produkt- og salgsfrekvensdata:
";
        $prompt .= json_encode($productsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $prompt .= "

Anvis for hver vare den optimale placeringszone (Zone A = tættest på pakkeudgang, Zone B = midtersektion, Zone C = fjernlager) samt en kort begrundelse.";

        return $this->queryMistral($prompt, $system);
    }
}
