<?php
// app/Models/StretcherNotificationLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StretcherNotificationLog extends Model
{
    protected $table = 'stretcher_notification_log';

    protected $fillable = [
        'stretcher_register_id',
        'notification_type',
        'notified_at'
    ];

    protected $casts = [
        'notified_at' => 'datetime'
    ];
}
