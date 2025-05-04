<?php

namespace App\DataForge\Sql;
use Illuminate\Support\Facades\Auth;

use DataForge\Sql;

class Users extends Sql
{
    public function default(&$data)
    {
        $query = Query('UsersList');
        $query->select('list', 'u.id, u.name, u.first_name, u.last_name, u.email, u.is_verified');
        $query->select('entity', 'u.id, u.name, u.first_name, u.last_name, u.email, u.is_verified,
                            CONCAT_WS(" ", country_code, local_number) AS phone');
        $query->select('total', 'COUNT(u.id) AS total');

        $query->from('users AS u');
        $query->left('phone_numbers AS pu ON pu.user_id=u.id');

         // Define anyone required filter to avoid data leaks.
        $query->filterAnyOneRequired('Id Or Email Or Phone Required', [
            'u.email = {email}',
            'u.phone = {phone}',
            'u.id = {id}'
        ]);

        return $query;
    }
}