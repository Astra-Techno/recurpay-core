<?php
namespace App\Services\SMS\Providers;

interface SMSProviderInterface {
    public function send(string $to, string $message): array;
}
