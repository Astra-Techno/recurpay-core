<?php

namespace App\DataForge\Task;

use DataForge\Task;

class Payment extends Task
{
    public function process($request)
    {
        $payment = \Sql('Payment:create', [
            'subscription_id' => $request->get('subscription_id'),
            'amount' => $request->get('amount'),
            'status' => 'success',
        ])->execute();

        // Update next payment date
        $subscription = \DataForge::getSubscription($request->get('subscription_id'));
        $subscription->update(['next_payment_date' => now()->addMonth()]);

        return $payment;
    }
}