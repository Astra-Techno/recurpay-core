<?php

namespace App\DataForge\Sql;

use DataForge\Sql;

class Payment extends Sql
{
    public function list(&$data)
    {
        $query = Query('PaymentList');
        $query->select('list', 'p.id, p.subscription_id, p.amount, p.status, p.payment_date');
        $query->from('payments AS p');
        $query->filter('p.subscription_id = {request.subscription_id}');
        return $query;
    }

    public function create(&$data)
    {
        $query = Query('PaymentCreate');
        $query->insert('payments', [
            'subscription_id' => '{request.subscription_id}',
            'amount' => '{request.amount}',
            'status' => '{request.status}',
            'payment_date' => now(),
        ]);
        return $query;
    }
}