<?php

namespace App\DataForge\Entity\System;

use Illuminate\Support\Facades\Auth;
use DataForge\Entity;

class Notification extends Entity
{
    public function init($id)
    {
        return \Sql('System\Notifications', ['id' => $id, 'select' => 'entity'])->fetchRow();
    }

    public function save($request)
    {
        $data = $request->toArray();
        if (empty($this->id))
            $data['added_by'] = Auth::id();

        $data = $this->TableSave($data, 'notifications', 'id');

        return true;
    }


}
