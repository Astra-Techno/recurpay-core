<?php

namespace App\DataForge\Sql;
use Illuminate\Support\Facades\Auth;

use DataForge\Sql;

class Users extends Sql
{
    public function default(&$data)
    {
        $query = Query('UsersList');
        $query->select('list', 'u.*');
        $query->select('entity', 'u.*');
        $query->select('total', 'COUNT(u.id) AS total');
        $query->from('users AS u');

         // Define anyone required filter to avoid data leaks.
         $query->filterAnyOneRequired('Id Or Email Or Phone Required', [
            'u.email = {email}',
            'u.phone = {phone}',
            'u.id = {id}'
        ]);

        return $query;
    }
}