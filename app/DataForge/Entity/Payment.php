<?php

namespace App\DataForge\Entity;

use DataForge\Entity;

class Payment extends Entity
{
    public function init($id)
    {
        return \Sql('Payment:list', ['id' => $id, 'select' => 'item'])->fetchRow();
    }

    public function getSubscription()
    {
        return \DataForge::getSubscription($this->subscription_id);
    }
}