<?php
/**
 * SmartPickDeepSeekAI - Integration med DeepSeek-R1 Ræsonneringsmodellen
 * Kan køre via DeepSeek API, Groq, Together.ai eller lokal Ollama/vLLM server.
 */
class SmartPickDeepSeekAI
{
    private $apiKey;
    private $apiUrl;
    private $model;

    public function __construct($apiKey = '', $apiUrl = 'https://api.deepseek.com/v1/chat/completions', $model = 'deepseek-reasoner')
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = !empty($apiUrl) ? $apiUrl : 'https://api.deepseek.com/v1/chat/completions';
        $this->model = !empty($model) ? $model : 'deepseek-reasoner';
    }

    /**
     * Send forespørgsel til DeepSeek-R1
     */
    public function queryDeepSeekR1($prompt, $systemPrompt = '')
    {
        $headers = [
            'Content-Type: application/json'
        ];
        if (!empty($this->apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.2
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $json = json_decode($response, true);
            if (isset($json['choices'][0]['message']['content'])) {
                return $json['choices'][0]['message']['content'];
            }
        }

        // Fallback ved fejl
        return "DeepSeek-R1 Respons (HTTP $httpCode): " . $response;
    }
}
