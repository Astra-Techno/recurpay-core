<?php

namespace App\DataForge\Task;
use Illuminate\Support\Facades\DB;
use DataForge\Task;

class Tenant extends Task
{
    public function save($request)
    {
        DB::beginTransaction();
        try {
            if ($id = $request->get('id')) {
                $tenant = \DataForge::getTenant($id);
            } else {
                $validatedData = $request->validate([
                    'name' => 'required',
                    'phone' => 'required',
                    'property_id' => 'required',
                ], [
                    'property_id.required' => 'Please select a property to assign this tenant.',
                ]);

                $tenant = \DataForge::newTenant($request->toArray());
            }

            if (!$tenant->save($request)) {
                DB::rollBack();
                return $this->raiseError($tenant->getError());
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->raiseError($e->getMessage());
        }
        DB::commit();
        return $tenant->toArray();
    }
}
