<?php

namespace App\DataForge\Entity;

use DataForge\Entity;

class Subscription extends Entity
{
    public function init($id)
    {
        return \Sql('Subscription:list', ['id' => $id, 'select' => 'item'])->fetchRow();
    }

    public function getService()
    {
        return \DataForge::getService($this->service_id);
    }
}