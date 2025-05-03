<?php

namespace App\DataForge\Task;
use Illuminate\Support\Facades\DB;
use DataForge\Task;

class User extends Task
{
    public function save($request)
    {
        DB::beginTransaction();
        try {


            $validatedData = $request->validate([
                'name' => 'required',
                'phone' => 'required',

            ]);

            if ($id = $request->get('id')) {
                $user = \DataForge::getUser($id);
                if (!$user) {
                    return $this->raiseError('User not found');
                }

                if (!$user->save($request)){
                    DB::rollBack();
                    return $this->raiseError($user->getError());
                }
            } else {
                return $this->raiseError("Invalid User to Update");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->raiseError($e->getMessage());
        }
        DB::commit();
        return $user->toArray();
    }
}
