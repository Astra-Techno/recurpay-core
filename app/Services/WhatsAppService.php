<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $accessToken;
    protected string $phoneNumberId;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.url');
        $this->accessToken = config('services.whatsapp.token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }

    /**
     * Send plain text WhatsApp message
     */
    public function sendText(string $recipientPhone, string $message): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipientPhone,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Send a pre-approved template message
     */
    public function sendTemplate(string $recipientPhone, string $templateName, array $parameters = [], string $lang = 'en_US'): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipientPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $lang,
                ],
            ]
        ];

        if (!empty($parameters)) {
            $payload['template']['components'] = [
                [
                    'type' => 'body',
                    'parameters' => array_map(fn($text) => ['type' => 'text', 'text' => $text], $parameters)
                ]
            ];
        }

        return $this->sendRequest($payload);
    }

    /**
     * Perform HTTP POST to WhatsApp Cloud API
     */
    protected function sendRequest(array $payload): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        try {
            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('WhatsApp API Error', ['payload' => $payload, 'response' => $response->json()]);
            return ['error' => true, 'details' => $response->json()];
        } catch (\Throwable $e) {
            Log::critical('WhatsApp API Exception', ['message' => $e->getMessage()]);
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }
}
