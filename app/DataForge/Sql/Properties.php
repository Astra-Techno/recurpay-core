<?php

namespace App\DataForge\Sql;
use Illuminate\Support\Facades\Auth;

use DataForge\Sql;

class Properties extends Sql
{
    public function default(&$data)
    {
        $query = Query('PropertiesList');
        $query->select('list', 'p.*');
        $query->select('entity', 'p.*');
        $query->select('total', 'COUNT(p.id) AS total');
        $query->from('properties AS p');
        $query->filter('p.landlord_id = '.Auth::id());
        $query->filterOptional('p.id={id}');

        return $query;
    }

    public function activeProperties(&$data)
    {
        $query = Query('PropertiesList');
        $query->select('list', 'p.*');
        $query->select('total', 'COUNT(DISTINCT p.id) AS total');
        $query->select('entity', 'p.*');
        $query->from('properties AS p');
        $query->left('property_tenants AS pt ON pt.property_id = p.id');
        $query->filter('p.landlord_id = '.Auth::id());

        if ($data['status'] == 'vacant') {
            $query->filter('pt.status = "vacant" OR pt.id IS NULL');
        } else {
            $query->filter('pt.status = {status}');
        }

        $query->filterOptional('p.id={id}');
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
