<?php

namespace App\DataForge\Task;

use DataForge\Task;

class Dashboard extends Task
{

    public function statsList($request)
    {

        $data = [];

        $data['stats'] = [
            'Total Properties' => \Sql('Properties', ['select_type' => 'total'])->result() ?? 0,
            'Occupied Units' => \Sql('Properties:activeProperties', ['select_type' => 'total', 'status' => 'active'])->result() ?? 0,
            'Pending Payments' => \Sql('PaymentTransactions', ['select_type' => 'total', 'status' => 'pending'])->result() ?? 0,
            'Revenue' => \Sql('PaymentTransactions', ['select_type' => 'revenue'])->result() ?? 0.00
        ];

        $data['occupancy'] = [
            \Sql('Properties:activeProperties', ['select_type' => 'total', 'status' => 'active'])->result() ?? 0,
            \Sql('Properties:activeProperties', ['select_type' => 'total', 'status' => 'vacant'])->result() ?? 0
        ];

        $data['statsCount'] = [
            'totalProperties' => \Sql('Properties', ['select_type' => 'total'])->result() ?? 0,
            'tenants' => \Sql('Tenants', ['select_type' => 'total'])->result() ?? 0,
            'pendingPayments' => \Sql('PaymentTransactions', ['select_type' => 'total', 'status' => 'pending'])->result() ?? 0
        ];


        return $data;

    }

}
