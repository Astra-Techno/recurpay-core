<?php

namespace App\DataForge\Entity;

use AstraTech\DataForge\Base\DataForge;
use DataForge\Entity;
use Illuminate\Support\Facades\Auth;

class User extends Entity
{
    public function init($id)
    {
        $params = $id;
        if (!is_array($id)) {
            $params = ['id' => $id];
        }

        $params['select'] = 'entity';
        return \Sql('Users', $params)->fetchRow();
    }

    public function create()
    {
        if (empty($this->phone))
            return false;

        $phone = DataForge::newPhoneNumber(['phone' => $this->phone]);
        if (!$phone) {
            $this->setError(DataForge::getError());
            return false;
        }

        $this->phone = $phone->full_number;
        if (!$this->save())
            return false;

        if ($phone->user_id)
            return true;

        $phone->user_id = $this->id;
        if ($phone->save()) {
            $this->setError($phone->getError());
            return false;
        }

        return true;
    }

    public function save($request = null)
    {
        $data = $request ? $request->toArray() : $this->toArray();

        if ($user = $this->init($data)) {
            $this->bind($user);
            return true;
        }

        if (empty($data['phone']) && empty($data['email'])) {
            $this->setError('Phone / email are required for user!.');
            return false;
        }

        $data = $this->TableSave($data, 'users', 'id');
        if (!$data)
            return false;

        $this->bind($data);

        return true;
    }
}