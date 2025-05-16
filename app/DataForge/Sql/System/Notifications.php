<?php

namespace App\DataForge\Sql\System;

use DataForge\Sql;

class Notifications extends Sql
{
    public function default(&$data)
    {
        $query = Query('Notifications');
        $query->select('list', "n.*");
        $query->select('item', "n.*");
        $query->select('total', 'COUNT(n.id) AS total');
        $query->from('FROM notifications AS n');
        $query->filterAnyOneRequired(
            'IdOrName&SectionMust',
            [
                'n.id={id}',
                'n.user_id={user_id}',
                 'n.user_id={request.user_id}'
            ]
        );
        $query->group('n.id');
        return $query;
    }
}
