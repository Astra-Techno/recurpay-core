<?php

namespace App\DataForge\Task;

use DataForge\Task;

class Subscription extends Task
{
    public function create($request)
    {
        $subscription = \Sql('Subscription:create', [
            'user_id' => $request->get('user_id'),
            'service_id' => $request->get('service_id'),
        ])->execute();

        return $subscription;
    }

    public function cancel($request)
    {
        $subscription = \Sql('Subscription:cancel', [
            'subscription_id' => $request->get('subscription_id'),
        ])->execute();

        return $subscription;
    }
}