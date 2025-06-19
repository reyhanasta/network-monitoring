<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevicesStatusLogs extends Model
{
    protected $fillable = ['device_id', 'is_online', 'latency', 'checked_at'];

    protected $casts = [
        'checked_at' => 'datetime',
        'is_online' => 'boolean',
    ];

    public function device()
    {
        return $this->belongsTo(Devices::class);
    }
}
