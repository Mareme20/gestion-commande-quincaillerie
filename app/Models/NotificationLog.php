<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'alert_signature',
        'alert_type',
        'alert_level',
        'alert_message',
        'alert_link',
        'alert_date',
        'sent_at',
        'context',
    ];

    protected $casts = [
        'alert_date' => 'date',
        'sent_at' => 'datetime',
        'context' => 'array',
    ];
}
