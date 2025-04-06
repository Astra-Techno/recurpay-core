<?php

namespace App\DataForge\Entity;

use DataForge\Entity;
use Illuminate\Support\Facades\Auth;

class Property extends Entity
{
    public function init($id)
    {
        return \Sql('Properties', ['id' => $id, 'select' => 'entity'])->fetchRow();
    }

    public function save($request)
    {
        $data = $request->toArray();
        if (empty($this->id))
            $data['landlord_id'] = Auth::id();

        if ($data = $this->TableSave($data, 'properties', 'id')) {
            $this->bind($data);
            return true;
        }

        return false;
    }
}