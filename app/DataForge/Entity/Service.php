<?php

namespace App\DataForge\Entity;

use DataForge\Entity;

class Service extends Entity
{
    public function init($id)
    {
        return \Sql('Service:item', ['id' => $id])->fetchRow();
    }
}