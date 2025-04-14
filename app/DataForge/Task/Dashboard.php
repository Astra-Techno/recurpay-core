<?php

namespace App\DataForge\Task;

use DataForge\Task;

class Dashboard extends Task
{

    public function statsList($request)
    {

       $data = [];

        $data['stats'] =  [
            'Total Properties' => \Sql('Properties', ['select_type' => 'total'])->result() ?? 0,
            'Occupied Units'=> \Sql('Properties:activeProperties', ['select_type' => 'total'])->result() ?? 0,
            'Pending Payments' => \Sql('PaymentTransactions', ['select_type' => 'total','status' => 'pending'])->result() ?? 0,
            'Revenue'=> \Sql('PaymentTransactions', ['select_type' => 'revenue'])->result() ?? 0.00
        ];


        return $data;

    }

}
