<?php

namespace App\DataForge\Sql;
use Illuminate\Support\Facades\Auth;

use DataForge\Sql;

class PhoneNumbers extends Sql
{
    public function default(&$data)
    {
        $query = Query('PhoneNumbersList');
        $query->select('list', 'pn.*');
        $query->select('entity', 'pn.*');
        $query->select('total', 'COUNT(pn.id) AS total');
        $query->from('phone_numbers AS pn');

        // Define anyone required filter to avoid data leaks.
        $query->filterAnyOneRequired('Id Or Email Or Phone Required', [
            'pn.full_number = {full_number}',
            'pn.id = {id}'
        ]);

        return $query;
    }
}