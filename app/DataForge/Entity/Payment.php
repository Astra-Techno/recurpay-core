<?php

namespace App\DataForge\Entity;

use AstraTech\DataForge\Base\DataForge;
use DataForge\Entity;
use Illuminate\Support\Facades\Auth;

class Payment extends Entity
{
    public function init($id)
    {
        //echo \Sql('Payments', ['id' => $id, 'select_type' => 'entity']);exit;
        return \Sql('Payments', ['id' => $id, 'select_type' => 'entity'])->fetchRow();
    }

    function attribGroups()
    {
        return [
            'PayNow' => 'Property,PaymentDue,PaymentOptions',
            'Edit' => 'Property,UserIds'
        ];
    }

    public function getProperty()
    {
        return DataForge::getProperty($this->property_id);
    }

    public function getPaymentUsers()
    {
        return \Sql('Payments:getUsers', ['payment_id' => $this->id])->fetchRowList();
    }

    public function getUserIds()
    {
        return explode(',', $this->userIds);
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
        foreach ($this->PaymentUsers as $paymentUser) {
            if (in_array($paymentUser['id'], $map_ids))
                continue;

            $paymentUser['status'] = 0;
            $this->TableSave($paymentUser, 'payment_users', 'id');
        }

        return $this->updateDue();
    }

    function updateDue()
    {
        $data = $this->PaymentDue;
        $data['id'] = $this->id;

        return $this->TableSave($data, 'payments', 'id');
    }

    function getFrequencyMap()
    {
        if ($this->period == 'daily')
            return ['num' => 1, 'unit' => 'days'];
        else if ($this->period == 'weekly')
            return ['num' => 7, 'unit' => 'days'];
        else if ($this->period == 'fortnightly')
            return ['num' => 14, 'unit' => 'days'];
        else if ($this->period == 'monthly')
            return ['num' => 1, 'unit' => 'months'];
        else if ($this->period == '6months')
            return ['num' => 6, 'unit' => 'months'];
        else if ($this->period == 'yearly')
            return ['num' => 1, 'unit' => 'year'];

        return false;
    }

    function getDueFrom()
    {
        if (!$this->paid_to || $this->paid_to == '0000-00-00')
            return $this->due_from;

        $paidTo = new \DateTimeImmutable($this->paid_to);
        $paidTo = $paidTo->modify("+1 days");
        return $paidTo->format('Y-m-d');
    }

    function getPaymentDue()
    {
        $frequency = $this->period;
        $dueFrom = $this->getDueFrom();
        $amount = $this->amount;
        $vacatedDate = ''; // $this->vacatedDate;

        $now = new \DateTimeImmutable();
        $start = new \DateTimeImmutable($dueFrom);
        $cutoff = $vacatedDate ? new \DateTimeImmutable($vacatedDate) : $now;

        $calcEndDate = $vacatedDate ? $cutoff : min($now, $cutoff);

        if ($this->period == 'onetime' || $calcEndDate < $start) {
            return [
                'next_due_date' => $start->format('Y-m-d'),
                'due_count' => 1,
                'total_due' => $amount,
            ];
        }

        if (!$this->FrequencyMap) {
            $this->setError("Unsupported frequency: $frequency");
            return false;
        }

        $frequencyNum = $this->FrequencyMap['num'];
        $unit = $this->FrequencyMap['unit'];
        $dueCount = 0;
        $i = 0;
        $totalDue = 0.00;
        $nextDueDate = null;

        while (true) {
            $startNum = $i * $frequencyNum;
            $endNum = ($i + 1) * $frequencyNum;

            $periodStart = (clone $start)->modify("+{$startNum} {$unit}");
            $periodEnd = (clone $start)->modify("+" . ($endNum) . " {$unit}");

            if ($periodStart > $calcEndDate) {
                $nextDueDate = $periodStart;
                break;
            }

            // Last period is partial
            if ($vacatedDate && $cutoff < $periodEnd) {
                $daysInPeriod = $periodStart->diff($periodEnd)->days;
                $daysUsed = $periodStart->diff($cutoff)->days;
                $partialAmount = round(($daysUsed / $daysInPeriod) * $amount, 2);
                $totalDue += $partialAmount;
                $nextDueDate = $cutoff;
                break;
            } else {
                $dueCount++;
                $totalDue += $amount;
                $nextDueDate = $periodEnd;
            }

            //echo $dueCount." - ".$nextDueDate->format('Y-m-d')." - ".$totalDue."\n";
            $i++;
        }

        return [
            'next_due_date' => $nextDueDate->format('Y-m-d'),
            'due_count' => $dueCount,
            'total_due' => round($totalDue, 2),
        ];
    }

    function getPaymentOptions()
    {
        return Sql('PaymentMethods:paymentAccounts', ['payment_user_id' => $this->Property->user_id])->fetchRowList();
    }

    function paid($request)
    {
        $user_id = Auth::id();
        if (!$user_id) {
            $this->setError('Invalid Access!');
            return false;
        }

        if (!in_array($user_id, $this->UserIds)) {
            $this->setError('Access Denied!');
            return false;
        }

        $paidAmount = $request->input('paidAmount');
        if ($paidAmount <= 1) {
            $this->setError('Invalid Paid Amount!');
            return false;
        }

        $paymentMethod = [];
        $paymentMethodId = $request->input('paymentMethodId');
        foreach ($this->PaymentOptions as $tmp) {
            if ($tmp['id'] == $paymentMethodId) {
                $paymentMethod = $tmp;
                break;
            }
        }

        if (!$paymentMethod) {
            $this->setError('Invalid Payment!');
            return false;
        }

        $tmp = [
            'payment_id' => $this->id,
            'from_id' => $user_id,
            'to_id' => $this->Property->user_id,
            'amount_paid' => $paidAmount,
            'payment_method_id' => $tmp['id'],
            'payment_mode' => $tmp['type'],
            'status' => 'paid',
            'due_date' => $this->next_due_date,
            'paid_on' => \DataForge::Date()
        ];

        $tmp = $this->TableSave($tmp, 'payment_transactions', 'id');
        if (!$tmp)
            return false;

        return true;
    }

    function markAsPaid($transactionId)
    {
        $user_id = Auth::id();
        if (!$user_id) {
            $this->setError('Invalid Access!');
            return false;
        }

        $transaction = DataForge::getTransaction($transactionId);
        if (!$transaction) {
            $this->setError('Invalid User Access!');
            return false;
        }

        if ($user_id != $transaction->to_id) {
            $this->setError('Access Denied!');
            return false;
        }

        if ($transaction->status != 'paid') {
            $this->setError('Payment already in process!');
            return false;
        }

        $tmp = $this->calculatePaid($transaction->amount_paid);
        $tmp['status'] = 'success';
        $tmp['id'] = $transaction->id;

        $tmp = $this->TableSave($tmp, 'payment_transactions', 'id');
        if (!$tmp)
            return false;

        $payment = ['id' => $this->id, 'paid_to' => $tmp['paid_to'], 'credit' => $tmp['balance']];
        $payment = $this->TableSave($payment, 'payments', 'id');
        if (!$payment)
            return false;

        $this->paid_to = $tmp['paid_to'];
        $this->credit = $tmp['balance'];

        return $this->updateDue();
    }

    function calculatePaid($paidAmount = 0.00)
    {
        $frequency = $this->period;
        $dueFrom = $this->dueFrom;
        $amount = $this->amount;
        $vacatedDate = ''; // $this->vacatedDate;
        $paidAmount = bcadd($paidAmount, $this->credit, 2);

        $now = new \DateTimeImmutable();
        $start = new \DateTimeImmutable($dueFrom);
        $cutoff = $vacatedDate ? new \DateTimeImmutable($vacatedDate) : '';

        if ($this->period == 'onetime') {
            $due = $amount;
            $credit = max(0, $paidAmount - $due);
            $paidToDate = $paidAmount >= $due ? $start : null;

            return [
                'paid_from' => $this->dueFrom,
                'paid_to' => $paidToDate ? $paidToDate->format('Y-m-d') : null,
                'credit_used' => $this->credit,
                'paid_periods' => 1,
                'balance' => round($credit, 2),
                'used_amount' => bcsub($paidAmount, $credit, 2)
            ];
        }

        if ($cutoff && $cutoff < $start) {
            return [
                'paid_from' => $this->dueFrom,
                'paid_to' => null,
                'credit_used' => $this->credit,
                'paid_periods' => 0,
                'balance' => round($paidAmount, 2),
                'used_amount' => 0
            ];
        }

        if (!$this->FrequencyMap) {
            $this->setError("Unsupported frequency: $frequency");
            return false;
        }

        $frequencyNum = $this->FrequencyMap['num'];
        $unit = $this->FrequencyMap['unit'];
        $i = 0;
        $credit = $paidAmount;
        $paidTo = null;
        $dueCount = 0;

        while (true) {
            $startNum = $i * $frequencyNum;
            $endNum = ($i + 1) * $frequencyNum;

            $periodStart = (clone $start)->modify("+{$startNum} {$unit}");
            $periodEnd = (clone $start)->modify("+{$endNum} {$unit}");

            /*if ($periodStart > $calcEndDate) {
                break;
            }*/

            if ($vacatedDate && $cutoff < $periodEnd) {
                $daysInPeriod = $periodStart->diff($periodEnd)->days;
                $daysUsed = $periodStart->diff($cutoff)->days;
                $partialAmount = round(($daysUsed / $daysInPeriod) * $amount, 2);

                if ($credit >= $partialAmount) {
                    $credit -= $partialAmount;
                    $paidTo = $cutoff;
                }
                break;
            }

            if ($credit >= $amount) {
                $credit -= $amount;
                $paidTo = $periodEnd;
                $dueCount++;
            } else {
                break;
            }

            $i++;
        }

        if ($paidTo) {
            $paidTo = (clone $paidTo)->modify('-1 days');
            $paidTo = $paidTo->format('Y-m-d');
        }

        return [
            'paid_from' => $this->dueFrom,
            'paid_to' => $paidTo,
            'credit_used' => $this->credit,
            'paid_periods' => $dueCount,
            'balance' => round($credit, 2),
            'used_amount' => bcsub($paidAmount, $credit, 2)
        ];
    }
}
