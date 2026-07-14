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
     * @return void
     */
    public static function log($userId, string $action, string $description): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'created_at' => now(),
        ]);
    }
}
