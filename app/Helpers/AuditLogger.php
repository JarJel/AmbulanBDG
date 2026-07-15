<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log an administrative or critical system action.
     *
     * @param  int|null  $userId
     * @param  string  $action
     * @param  string  $description
     * @param  mixed  $causer
     * @param  mixed  $subject
     * @param  array|null  $properties
     * @return void
     */
    public static function log($userId, string $action, string $description, $causer = null, $subject = null, ?array $properties = null): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'causer_id' => $causer ? $causer->getKey() : null,
            'causer_type' => $causer ? get_class($causer) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'subject_type' => $subject ? get_class($subject) : null,
            'properties' => $properties,
            'created_at' => now(),
        ]);
    }
}
