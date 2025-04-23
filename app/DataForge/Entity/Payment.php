<?php

namespace App\DataForge\Entity;

use DataForge\Entity;
use Illuminate\Support\Facades\Auth;

class Payment extends Entity
{
    public function init($id)
    {
        //echo \Sql('Payments', ['id' => $id, 'select_type' => 'entity']);exit;
        return \Sql('Payments', ['id' => $id, 'select_type' => 'entity'])->fetchRow();
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
        foreach ($this->PaymentUsers AS $paymentUser)
        {
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
        if (!$this->paid_to)
            return $this->due_from;
    
        $paidTo = new \DateTimeImmutable($this->paid_to);
        $paidTo->modify("+1 days");
        return $paidTo->format('Y-m-d');
    }

    function getPaymentDue()
    {
        $frequency = $this->period;
        $dueFrom = $this->dueFrom;
        $amount = $this->amount;
        $vacatedDate = ''; // $this->vacatedDate;

        $now = new \DateTimeImmutable();
        $start = new \DateTimeImmutable($dueFrom);
        $cutoff = $vacatedDate ? new \DateTimeImmutable($vacatedDate) : $now;

        $calcEndDate = $vacatedDate ? $cutoff : min($now, $cutoff);

        
        if ($this->period == 'onetime') {
            return [
                'next_due_date' => $start->format('Y-m-d'),
                'due_count' => 1,
                'total_due' => $amount,
            ];
        }

        if ($calcEndDate < $start) {
            return [
                'next_due_date' => $start->format('Y-m-d'),
                'due_count' => 0,
                'total_due' => 0.00,
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
            $periodEnd = (clone $start)->modify("+".($endNum)." {$unit}");

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

}
