<?php

namespace App\DataForge\Entity;

use DataForge\Entity;
use Illuminate\Support\Facades\Auth;

class Payment extends Entity
{
    public function init($id)
    {
        return \Sql('Payments', ['id' => $id, 'select' => 'entity'])->fetchRow();
    }

    public function getPaymentUsers()
    {
        return \Sql('Payments:getUsers', ['payment_id' => $this->id])->fetchRowList();
    }

    public function save($request)
    {
        $data = $request->toArray();
        if (empty($this->id))
            $data['created_by'] = Auth::id();

        $tmp = $this->TableSave($data, 'payments', 'id');
        if (!$tmp)
            return false;

        $this->bind($tmp);

        $map_ids = [];
        foreach ($data['users'] as $user_id) {
            $tmp = ['payment_id' => $this->id, 'user_id' => $user_id, 'status' => 1];
            $tmp = $this->TableSave($tmp, 'payment_users', 'payment_id&user_id');
            if (!$tmp)
                return false;

            $map_ids[] = $tmp['id'];
        }

        // Delete remvoed users.
        foreach ($this->PaymentUsers AS $paymentUser)
        {
            if (in_array($paymentUser['id'], $map_ids))
                continue;

            $paymentUser['status'] = 0;
            $this->TableSave($paymentUser, 'payment_users', 'id');
        }

        return true;
    }
}