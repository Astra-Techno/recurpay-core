<?php
namespace App\Services\SMS\Providers;

use Illuminate\Support\Facades\Http;

class Msg91Provider implements SMSProviderInterface
{
    public function send(string $to, string $message): array
    {
        $apiKey = config('sms.providers.msg91.api_key');
        $senderId = config('sms.providers.msg91.sender_id');

        $response = Http::post("https://control.msg91.com/api/v5/message", [
            'authkey' => $apiKey,
            'message' => $message,
            'sender' => $senderId,
            'route' => '4',
            'country' => '91',
            'sms' => [[
                'to' => [$to],
                'message' => $message,
            ]]
        ]);

        return [
            'status' => $response->successful() ? 'success' : 'failed',
            'response' => $response->json(),
        ];
    }
}
