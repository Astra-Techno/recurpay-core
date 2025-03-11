<?php

namespace App\DataForge\Sql;

use DataForge\Sql;

class Subscription extends Sql
{
    public function list(&$data)
    {
        $query = Query('SubscriptionList');
        $query->select('list', 's.id, s.user_id, s.service_id, s.start_date, s.next_payment_date, s.status');
        $query->select('item', 's.*, u.name AS user_name, sv.name AS service_name');
        $query->from('subscriptions AS s');
        $query->join('users AS u', 's.user_id = u.id');
        $query->join('services AS sv', 's.service_id = sv.id');
        $query->filter('s.status = "active"');
        return $query;
    }

    public function byUser(&$data)
    {
        $query = Query('SubscriptionByUser');
        $query->select('list', 's.id, s.service_id, s.start_date, s.next_payment_date, s.status');
        $query->from('subscriptions AS s');
        $query->filter('s.user_id = {request.user_id}');
        return $query;
    }

    public function create(&$data)
    {
        $query = Query('SubscriptionCreate');
        $query->insert('subscriptions', [
            'user_id' => '{request.user_id}',
            'service_id' => '{request.service_id}',
            'start_date' => now(),
            'next_payment_date' => now()->addMonth(),
            'status' => 'active',
        ]);
        return $query;
    }

    public function cancel(&$data)
    {
        $query = Query('SubscriptionCancel');
        $query->update('subscriptions', [
            'status' => 'cancelled',
        ])->filter('id = {request.subscription_id}');
        return $query;
    }
}