<?php

namespace App\DataForge\Task;

use DataForge\Task;

class Tenant extends Task
{
    public function save($request)
    {
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

        if (!$tenant->save($request))
            return $this->raiseError($tenant->getError());

        return $tenant->toArray();
    }
}