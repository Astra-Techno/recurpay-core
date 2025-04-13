<?php

namespace App\DataForge\Sql;
use Illuminate\Support\Facades\Auth;

use DataForge\Sql;

class Tenants extends Sql
{
    public function default(&$data)
    {
        $query = Query('TenantsList');
        $query->select('list', 'pt.*');
        $query->select('entity', 'pt.*, p.name AS property');
        $query->select('autocomplete', 'pt.user_id AS value, pt.name AS label');
        $query->select('total', 'COUNT(pt.id) AS total');

        $query->from('property_tenants AS pt');
        $query->inner('properties AS p ON p.id=pt.property_id');
        $query->filter('p.landlord_id = '.Auth::id());
        $query->filterOptional('pt.id={id}');

        $query->filterOptional('pt.property_id={request.property_id}');

        return $query;
    }
}