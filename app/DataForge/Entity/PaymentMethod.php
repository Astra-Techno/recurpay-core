<?php

namespace App\DataForge\Entity;

use DataForge\Entity;
use Illuminate\Support\Facades\Auth;

class PaymentMethod extends Entity
{
    public function init($id)
    {
        return \Sql('PaymentMethods', ['id' => $id, 'select' => 'entity'])->fetchRow();
    }

    public function save($request)
    {
        $data = $request->toArray();
        if (empty($this->id))
            $data['user_id'] = Auth::id();
        else if ($this->user_id != Auth::id()) {
            $this->setError('Invalid Access!');
            return false;
        }

        if ($data = $this->TableSave($data, 'payment_methods', 'id')) {
            $this->bind($data);
            return true;
        }

        return false;
    }
}