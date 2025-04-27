<?php

namespace App\DataForge\Sql;
use Illuminate\Support\Facades\Auth;

use DataForge\Sql;

class PaymentTransactions extends Sql
{
    public function default(&$data)
    {

        $query = Query('PaymentTransactionList');
        $query->select('list', 'ptr.*, p.name AS property, GROUP_CONCAT(DISTINCT pu.user_id) AS users,pp.type AS payment_type,
        (select name from users where id=ptr.user_id) AS tenant_name');
        $query->select('entity', 'pp.*, p.name AS property, GROUP_CONCAT(pu.user_id) AS users');
        $query->select('total', 'COUNT(ptr.id) AS total');
        $query->select('revenue', 'SUM(amount_paid) AS revenue');

        $query->from('payments AS pp');
        $query->join('properties AS p', 'p.id = pp.property_id');
        $query->inner('payment_users AS pu ON pu.payment_id  = pp.id AND pu.status=1');
        $query->inner('payment_transactions AS ptr ON ptr.payment_id  = pp.id AND pu.status=1');
        $query->inner('property_tenants AS pt ON pt.user_id=pu.user_id');

        $query->filter('p.user_id = '.Auth::id());

        if ($data['select_type'] == 'revenue') {
            $query->filter('ptr.status = "completed"');
        }
        $query->filterOptional('ptr.id={request.id}');
        $query->filterOptional('ptr.status={status}');
        $query->filterOptional('p.id={request.property_id}');

        $query->group('pp.id');

        return $query;
    }

}
