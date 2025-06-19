<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    protected $fillable = ['device_id', 'channel', 'message', 'sent_at'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Devices::class);
    }
}
