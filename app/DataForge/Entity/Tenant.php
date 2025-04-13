<?php

namespace App\DataForge\Entity;

use DataForge\Entity;
use Illuminate\Support\Facades\Auth;

class Tenant extends Entity
{
    public function init($id)
    {
        return \Sql('Tenants', ['id' => $id, 'select' => 'entity'])->fetchRow();
    }

    public function save($request)
    {
        $data = $request->toArray();
        if (empty($this->id))
            $data['added_by'] = Auth::id();

        $data = $this->TableSave($data, 'property_tenants', 'id');
        if (!$data)
            return false;

        $this->bind($data);
        if ($this->verified)
            return true;

        $user = \DataForge::newUser(['phone' => $this->phone, 'name' => $this->name]);
        if (!$user) {
            $this->getError(\DataForge::getError());
            return false;
        }

        $this->user_id = $user->id;
        $data = $this->TableSave($this->toArray(), 'property_tenants', 'id');
        if (!$data)
            return false;

        return true;
    }
}