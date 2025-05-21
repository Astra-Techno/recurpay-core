<?php
namespace App\Services\SMS\Providers;

use Illuminate\Support\Facades\Http;

class TextLocalProvider implements SMSProviderInterface
{
    public function send(string $to, string $message): array
    {
        $apiKey = config('sms.providers.textlocal.api_key');

        $response = Http::asForm()->post("https://api.textlocal.in/send/", [
            'apikey' => $apiKey,
            'numbers' => $to,
            'message' => $message,
            'sender' => 'TXTLCL'
        ]);

        return [
            'status' => $response->successful() ? 'success' : 'failed',
            'response' => $response->json(),
        ];
    }
}
