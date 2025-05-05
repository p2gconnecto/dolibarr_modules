<?php

class DiagnosiDigitaleAI
{
    private $endpoint;

    public function __construct()
    {
        global $conf;

        // Configura l'endpoint AI (puoi personalizzarlo)
        $this->endpoint = DOL_URL_ROOT . '/ai/script/interface.php';
    }

    /**
     * Invia una richiesta all'AI per generare contenuti
     *
     * @param string $prompt Il prompt da inviare all'AI
     * @return array Risultato della richiesta (success, content, error)
     */

    public function generateContent($prompt)
    {
        $prompt = htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');
        $apiKey = '***REMOVED***'; // Sostituisci con la tua chiave API
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => 'gpt-3.5-turbo', // Specifica il modello da utilizzare
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.7
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $decodedResponse = json_decode($response, true);
            if (isset($decodedResponse['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'content' => trim($decodedResponse['choices'][0]['message']['content'])
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No content returned from AI'
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => 'HTTP error code: ' . $httpCode
            ];
        }
    }

    /**
     * Invia una richiesta HTTP POST all'endpoint AI
     *
     * @param array $data I dati da inviare
     * @return array Risultato della richiesta
     */
    private function sendRequest($data)
    {
        $ch = curl_init($this->endpoint);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $decodedResponse = json_decode($response, true);
            if (isset($decodedResponse['success']) && $decodedResponse['success']) {
                return $decodedResponse;
            } else {
                return [
                    'success' => false,
                    'error' => $decodedResponse['error'] ?? 'Unknown error'
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => 'HTTP error code: ' . $httpCode
            ];
        }
    }
}