<?php
namespace App\Services\SMS\Providers;

use Illuminate\Support\Facades\Http;

class Fast2SMSProvider implements SMSProviderInterface
{
    public function send(string $to, string $message): array
    {
        $apiKey = config('sms.providers.fast2sms.api_key');

        $response = Http::withHeaders([
            'authorization' => $apiKey,
            'Content-Type' => 'application/json'
        ])->post("https://www.fast2sms.com/dev/bulkV2", [
            'route' => 'v3',
            'sender_id' => 'TXTIND',
            'message' => $message,
            'language' => 'english',
            'flash' => 0,
            'numbers' => $to
        ]);

        return [
            'status' => $response->successful() ? 'success' : 'failed',
            'response' => $response->json(),
        ];
    }
}
