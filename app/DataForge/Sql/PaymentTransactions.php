<?php

namespace App\DataForge\Sql;
use Illuminate\Support\Facades\Auth;

use DataForge\Sql;

class PaymentTransactions extends Sql
{
    public function default(&$data)
    {

        $query = Query('PaymentTransactionList');
        $query->select('list', 'ptr.*, p.name AS property, GROUP_CONCAT(DISTINCT pu.user_id) AS users, pay.type AS payment_type,
        (select name from users where id=ptr.from_id) AS tenant_name');
        $query->select('entity', 'ptr.*, p.name AS property, GROUP_CONCAT(DISTINCT pu.user_id) AS users');
        $query->select('total', 'COUNT(ptr.id) AS total');
        $query->select('total_amount', 'SUM(ptr.amount_paid) AS total_amount');
        $query->select('revenue', 'SUM(
                                    CASE WHEN ptr.from_id='.Auth::id().' THEN (amount_paid * -1) 
                                        ELSE amount_paid END
                                )  AS revenue');

        $query->from('payment_transactions AS ptr');
        $query->inner('payments AS pay ON ptr.payment_id  = pay.id');
        $query->inner('properties AS p', 'p.id = pay.property_id');
        $query->inner('payment_users AS pu ON pu.payment_id  = pay.id');

        $query->filter('(ptr.from_id='.Auth::id(). ' OR ptr.to_id='.Auth::id().')');

        if ($data['select_type'] == 'revenue') {
            $query->filter('ptr.status = "completed"');
        }
        $query->filterOptional('ptr.id={request.id}');
        $query->filterOptional('ptr.status={status}');
        $query->filterOptional('p.id={request.property_id}');
        $query->filterOptional('ptr.payment_id = {request.payment_id}');
        $query->filterOptional('{request.credits}=1 AND ptr.to_id='.Auth::id());
        $query->filterOptional('{request.debits}=1 AND ptr.from_id='.Auth::id());

        $query->group('ptr.id');

        return $query;
    }

}
