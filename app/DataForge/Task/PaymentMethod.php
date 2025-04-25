<?php

namespace App\DataForge\Task;

use DataForge\Task;

class PaymentMethod extends Task
{
    public function save($request)
    {
        $type = $request->input('type');
        if ($type == 'bank') {
            $validatedData = $request->validate([
                'account_holder_name' => 'required',
                'bank_name' => 'required',
                'account_number' => 'required',
                'ifsc_code' => 'required'
            ]);
        } else if ($type == 'upi') {
            $validatedData = $request->validate([
                'upi_id' => 'required'
            ]);
        } else 
            return $this->raiseError('Invalid payment type!');

        if ($id = $request->get('id'))
            $paymentMethod = \DataForge::getPaymentMethod($id);
        else
            $paymentMethod = \DataForge::newPaymentMethod($request->toArray());

        if (!$paymentMethod->save($request))
            return $this->raiseError($paymentMethod->getError());

        return $paymentMethod->toArray();
    }
}