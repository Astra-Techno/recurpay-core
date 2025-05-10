<?php

namespace App\DataForge\Sql;

use Illuminate\Support\Facades\Auth;

use DataForge\Sql;

class Payments extends Sql
{
    public function pay_status()
    {
        $condition = "CASE WHEN pp.total_due > 0  AND pt.user_id=" . Auth::id() . " THEN 'due'
                             WHEN pp.total_due > 0 AND p.user_id=" . Auth::id() . " THEN 'pending'
                             WHEN pp.total_due <= 0 AND pp.due_from > CURRENT_DATE THEN 'upcoming'
                             WHEN pp.total_due <= 0 AND pp.due_from <= CURRENT_DATE THEN 'paid'
                             ELSE 'other' END AS pay_status";
        $condition = "
            CASE WHEN ppt.id IS NOT NULL THEN ppt.status
                WHEN pp.total_due > 0  AND pt.user_id=" . Auth::id() . " THEN 'due'
                WHEN pp.total_due > 0  AND pt.user_id=" . Auth::id() . " THEN 'pending'
                ELSE 'upcoming' END AS pay_status
        ";
        return $condition;
    }
    public function default(&$data)
    {
        $query = Query('PaymentList');

        $query->select('list', "pp.id,p.id as property_id, p.name AS property, pp.amount, pp.currency, pp.period,
                        pp.total_due, pp.next_due_date, p.address1, DATEDIFF(pp.next_due_date, CURDATE()) AS due_in_days,
                        CASE WHEN pp.type='other' AND pp.other_type !='' THEN pp.other_type
                        ELSE pp.type END AS type, ppt.amount_paid, ppt.payment_mode, ppt.id AS transaction_id, (SELECT name FROM users WHERE id = pt.user_id) as user," . $this->pay_status());
        $query->select('entity', "pp.*, p.name AS property, GROUP_CONCAT(pu.user_id) AS userIds,(SELECT name FROM users WHERE id = pt.user_id) as user,pt.user_id as user_id," . $this->pay_status());
        $query->select('total', 'COUNT(pp.id) AS total');

        $query->from('payment_users AS pu');
        $query->inner('payments AS pp ON pu.payment_id = pp.id');
        $query->inner('properties AS p ON p.id = pp.property_id');
        $query->inner('property_tenants AS pt ON pt.user_id=pu.user_id');
        $query->left('payment_transactions AS ppt ON ppt.payment_id=pp.id AND ppt.due_date=pp.next_due_date');

        $query->filter('pu.status = 1');
        $query->filter('p.user_id = ' . Auth::id() . ' OR pu.user_id = ' . Auth::id());

        $query->filterAnyOneRequired('PropertyIdOrDueOrPaymentMust', [
            'pp.id = {request.id}',
            'pp.id = {id}',
            'pp.property_id = {request.property_id}',
            '{request.Due}=1 AND (ppt.id IS NULL OR ppt.status="failed") AND pp.total_due > 0 AND pt.user_id=' . Auth::id(),
            '{request.Pending}=1 AND pp.total_due > 0 AND p.user_id=' . Auth::id()
        ]);

        $query->group('pp.id');

        $query->order('pp.next_due_date', 'ASC');

        return $query;
    }

    public function allPayments($data)
    {
        $query = Query('PaymentList');
        $query->select('list', "pp.id,p.id as property_id, p.name AS property, pp.amount, pp.currency, pp.period,
                        pp.total_due, pp.next_due_date, p.address1, DATEDIFF(pp.next_due_date, CURDATE()) AS due_in_days,
                        CASE WHEN pp.type='other' AND pp.other_type !='' THEN pp.other_type
                        ELSE pp.type END AS type,
                        CASE WHEN pp.total_due > 0  AND pt.user_id=" . Auth::id() . " THEN 'due'
                        WHEN pp.total_due > 0 AND p.user_id=" . Auth::id() . " THEN 'pending'
                        WHEN pp.total_due <= 0 THEN 'paid'
                        ELSE 'other' END AS pay_status,
                        p.name as group_label");
        $query->select('entity', 'pp.*, p.name AS property, GROUP_CONCAT(pu.user_id) AS userIds');
        $query->select('total', 'COUNT(pp.id) AS total');
        $query->select('group_total', 'COUNT(pp.id) AS total,p.name as group_label');

        $query->from('payment_users AS pu');
        $query->inner('payments AS pp ON pu.payment_id = pp.id');
        $query->inner('properties AS p ON p.id = pp.property_id');
        $query->inner('property_tenants AS pt ON pt.user_id=pu.user_id');

        $query->filter('pu.status = 1');
        $query->filter('p.user_id = ' . Auth::id() . ' OR pu.user_id = ' . Auth::id());

        $select = empty($data['select_type']) ? request('select') : $data['select_type'];
        if ($select == 'group_total')
            $query->group('group_label');
        else
            $query->group('pp.id');

        $query->order('pp.next_due_date', 'ASC');

        return $query;
    }

    public function getUsers(&$data)
    {
        $query = Query('PaymentUsersList');
        $query->select('list', 'pu.*');

        $query->from('payment_users AS pu');
        $query->inner('payments AS pp ON pp.id=pu.payment_id AND pu.status=1');

        $query->filter('pp.id={payment_id}');

        return $query;
    }
}
