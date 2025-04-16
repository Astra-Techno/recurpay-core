<?php

namespace App\DataForge\Sql;
use Illuminate\Support\Facades\Auth;

use DataForge\Sql;

class Payments extends Sql
{
    public function default(&$data)
    {
        $data['property_id'] = $data['property_id'] ?? request('property_id') ?? null;
        $query = Query('PaymentList');
        $query->select('list', 'pp.*,p.name AS property, GROUP_CONCAT(pu.user_id) AS users');
        $query->select('entity', 'pp.*, p.name AS property, GROUP_CONCAT(pu.user_id) AS users');
        $query->select('total', 'COUNT(pp.id) AS total');

        $query->from('payments AS pp');

        $query->inner('payment_users AS pu ON pu.payment_id  = pp.id AND pu.status=1');
        $query->inner('properties AS p ON p.id = pp.property_id');
        $query->inner('property_tenants AS pt ON pt.user_id=pu.user_id');

        $query->filter('p.landlord_id = '.Auth::id());
        $query->filterOptional('pp.id={id}');

        $query->filterOptional('p.id={property_id}');

        $query->group('pp.id');

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
