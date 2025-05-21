<?php
namespace App\Services\SMS;

use App\Services\SMS\Providers\SMSProviderInterface;

class SMSService
{
    protected SMSProviderInterface $provider;

    public function __construct()
    {
        $providerClass = config('sms.default_provider');
        $this->provider = app($providerClass);
    }

    public function send(string $to, string $message): array
    {
        return $this->provider->send($to, $message);
    }
}
