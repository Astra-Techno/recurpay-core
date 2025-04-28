<?php

namespace App\DataForge\Sql;
use Illuminate\Support\Facades\Auth;

use DataForge\Sql;

class PaymentMethods extends Sql
{
    public function default(&$data)
    {

        $query = Query('PaymentMethodsList');
        $query->select('list', 'pm.*');
        $query->select('entity', 'pm.*');
        $query->select('total', 'COUNT(pm.id) AS total');

        $query->from('payment_methods AS pm');
        $query->filter('pm.user_id = '.Auth::id());

        $query->filterOptional('pm.id = {id}');
        $query->filterOptional('pm.id = {request.id}');

        $query->order('pm.id', 'DESC');

        return $query;
    }

    public function paymentAccounts(&$data)
    {

        $query = Query('PaymentAccountsList');
        $query->select('list', 'pm.*');
        $query->select('total', 'COUNT(pm.id) AS total');

        $query->from('payment_methods AS pm');
        $query->filter('pm.user_id = {payment_user_id} OR pm.id=1');

        $query->filterOptional('pm.id = {id}');
        $query->filterOptional('pm.id= {request.payment_method_id}');

        $query->order('pm.id', 'DESC');

        return $query;
    }
}