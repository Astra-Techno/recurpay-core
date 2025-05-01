<?php

namespace App\DataForge\Task;

use DataForge\Task;

class Payment extends Task
{
    public function save($request)
    {
        if ($id = $request->get('id')) {
            $payment = \DataForge::getPayment($id);
        } else {
            $validatedData = $request->validate([
                'period' => 'required',
                'amount' => 'required',
                'due_from' => 'required',
                'property_id' => 'required',
                'users' => 'required',
            ], [
                'property_id.required' => 'Please select a property to assign this payment.',
                'users.required' => 'Please select a tenant to assign this payment.',
            ]);

            $payment = \DataForge::newPayment($request->toArray());
        }

        if (!$payment->save($request))
            return $this->raiseError($payment->getError());

        return $payment->toArray();
    }

    public function paid($request)
    {
        $validatedData = $request->validate([
            'paymentId' => 'required',
            'paymentMethodId' => 'required',
            'paidAmount' => 'required|numeric'
        ], [
            'paidAmount.required' => 'Please enter valid amount.'
        ]);

        $payment = \DataForge::getPayment($request->input('paymentId'));
        if (!$payment->paid($request))
            return $this->raiseError($payment->getError());

        return true;
    }

    public function markAsPaid($request)
    {
        $validatedData = $request->validate([
            'id' => 'required',
            'payment_id' => 'required',
            'amount_paid' => 'required|numeric'
        ], [
            'amount_paid.required' => 'Please enter valid amount.'
        ]);

        $payment = \DataForge::getPayment($request->input('payment_id'));
        if (!$payment->markAsPaid($request->input('id')))
            return $this->raiseError($payment->getError());

        return true;
    }
}
