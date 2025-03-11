<?php

namespace App\DataForge\Sql;

use DataForge\Sql;

class Service extends Sql
{
    public function list(&$data)
    {
        $query = Query('ServiceList');
        $query->select('list', 's.id, s.name, s.price, s.billing_cycle');
        $query->from('services AS s');
        return $query;
    }

    public function item(&$data)
    {
        $query = Query('ServiceItem');
        $query->select('item', 's.*');
        $query->from('services AS s');
        $query->filter('s.id = {request.id}');
        return $query;
    }
}