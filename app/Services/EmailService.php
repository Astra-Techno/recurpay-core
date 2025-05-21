<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class EmailService
{
    public function sendRaw($to, $subject, $htmlBody, $attachments = [], $queue = false)
    {
        $callback = function ($message) use ($to, $subject, $htmlBody, $attachments) {
            $message->to($to)->subject($subject)->setBody($htmlBody, 'text/html');

            foreach ($attachments as $file => $name) {
                $message->attach($file, ['as' => $name]);
            }
        };

        return $queue ? Mail::queue([], [], $callback) : Mail::send([], [], $callback);
    }

    public function sendTemplate($to, $template, array $data = [], $attachments = [], $queue = false)
    {
        $renderedBody = View::make(['template' => $template->body], $data)->render();
        $renderedSubject = View::make(['template' => $template->subject], $data)->render();

        return $this->sendRaw($to, $renderedSubject, $renderedBody, $attachments, $queue);
    }
}
