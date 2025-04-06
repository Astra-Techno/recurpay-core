<?php

namespace App\DataForge\Task;

use DataForge\Task;

class Property extends Task
{
    public function save($request)
    {
        if ($id = $request->get('id')) {
            $property = \DataForge::getProperty($id);
        } else {
            $validatedData = $request->validate([
                'address1' => 'required',
                'city' => 'required',
                'state' => 'required',
                'postal_code' => 'required'
            ]);

            $property = \DataForge::newProperty($request->toArray());
        }

        if (!$property->save($request))
            return $this->raiseError($property->getError());

        return $property->toArray();
    }
}