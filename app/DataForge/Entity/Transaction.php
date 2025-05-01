<?php

namespace App\DataForge\Entity;

use AstraTech\DataForge\Base\DataForge;
use DataForge\Entity;
use Illuminate\Support\Facades\Auth;

class Transaction extends Entity
{
    public function init($id)
    {
        //echo \Sql('Payments', ['id' => $id, 'select_type' => 'entity']);exit;
        return \Sql('PaymentTransactions', ['id' => $id, 'select_type' => 'entity'])->fetchRow();
    }
}