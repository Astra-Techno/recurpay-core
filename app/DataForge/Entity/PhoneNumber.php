<?php

namespace App\DataForge\Entity;

use DataForge\Entity;
use Illuminate\Support\Facades\Auth;

class PhoneNumber extends Entity
{
    public function init($id)
    {
        $params = $id;
        if (!is_array($id)) {
            $params = ['id' => $id];
        }

        $params['select'] = 'entity';

        return \Sql('PhoneNumbers', $params)->fetchRow();
    }

    function create()
    {
        if (empty($this->phone))
            return false;

        $phone = $this->parsePhoneNumber($this->phone);
        $this->bind($phone);

        if ($this->find($this->toArray()))
            return true;

        return $this->save();
    }

    function find($input)
    {
        if ($data = $this->init($input)) {
            $this->bind($data);
            return true;
        }

        return false;
    }

    function save($request = null)
    {
        $data = $request ? $request->toArray() : $this->toArray();

        $data = $this->TableSave($data, 'phone_numbers', 'id');
        if (!$data)
            return false;

        $this->id = $data['id'];

        return true;
    }

    function parsePhoneNumber(string $phone): array
    {
        // Remove all non-digit and non-plus characters
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // Default country code for India
        $countryCode = '+91';
        $number = $cleaned;
        
        // If number starts with +, extract country code
        if (strpos($cleaned, '+') === 0) {
            // Handle known country codes (India +91, US +1, etc.)
            if (preg_match('/^\+91(\d{10})/', $cleaned, $matches)) {
                $countryCode = '+91';
                $number = $matches[1]; // 10 digit Indian number
            }
            elseif (preg_match('/^\+1(\d{10})/', $cleaned, $matches)) {
                $countryCode = '+1';
                $number = $matches[1]; // 10 digit US/Canada number
            }
            // Add other country patterns as needed
            else {
                // Fallback - extract first 1-4 digits after +
                if (preg_match('/^\+(\d{1,4})(\d+)/', $cleaned, $matches)) {
                    $countryCode = '+' . $matches[1];
                    $number = $matches[2];
                }
            }
        }
        // Handle numbers without + but with country code
        elseif (preg_match('/^91(\d{10})/', $cleaned, $matches)) {
            $countryCode = '+91';
            $number = $matches[1];
        }
        // Handle local Indian numbers (10 digits)
        elseif (preg_match('/^(\d{10})/', $cleaned, $matches)) {
            $number = $matches[1];
        }
        
        // Format the local number (Indian format: 5-4-1)
        $formattedLocal = $number;
        if (strlen($number) === 10) {
            $formattedLocal = substr($number, 0, 5) . ' ' . substr($number, 5, 4) . ' ' . substr($number, 9, 1);
        }
        
        return [
            'phone' => $phone,
            'full_number' => str_replace('+', '', $countryCode) . $number,
            'full_international' => $countryCode . $number,
            'country_code' => $countryCode,
            'local_number' => $number,
            'formatted_international' => $countryCode . ' ' . 
                                    substr($number, 0, 5) . ' ' . 
                                    substr($number, 5),
            'formatted_local' => $formattedLocal,
            'is_valid' => $this->validateIndianNumber($number)
        ];
    }

    private function validateIndianNumber(string $number): bool
    {
        return preg_match('/^[6-9]\d{9}$/', $number) === 1;
    }
}