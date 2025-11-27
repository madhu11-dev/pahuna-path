<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueEmailWithoutPlusAddressing implements ValidationRule
{
    protected $table;
    protected $column;
    protected $ignoreId;

    public function __construct($table = 'users', $column = 'email', $ignoreId = null)
    {
        $this->table = $table;
        $this->column = $column;
        $this->ignoreId = $ignoreId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Normalize the input email by removing plus addressing
        $normalizedEmail = $this->normalizeEmail($value);

        // Get all emails from the database
        $query = DB::table($this->table);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        $existingEmails = $query->pluck($this->column);

        // Check if any existing email normalizes to the same value
        foreach ($existingEmails as $existingEmail) {
            if ($this->normalizeEmail($existingEmail) === $normalizedEmail) {
                $fail('This email address is already registered (plus addressing variations are not allowed).');
                return;
            }
        }
    }

    /**
     * Normalize email by removing plus addressing
     * Example: john+test@gmail.com becomes john@gmail.com
     */
    private function normalizeEmail($email)
    {
        // Convert to lowercase
        $email = strtolower(trim($email));
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return $email; // Invalid email format, return as is
        }

        $localPart = $parts[0];
        $domainPart = $parts[1];

        // Remove plus addressing (everything from + to @)
        if (strpos($localPart, '+') !== false) {
            $localPart = substr($localPart, 0, strpos($localPart, '+'));
        }


        // as Gmail ignores dots in email addresses
        if (in_array($domainPart, ['gmail.com', 'googlemail.com'])) {
            $localPart = str_replace('.', '', $localPart);
        }

        return $localPart . '@' . $domainPart;
    }
}
