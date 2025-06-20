<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devices extends Model
{
    protected $fillable = ['name', 'ip_address', 'type', 'last_seen_at', 'is_online'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'is_online' => 'boolean',
    ];

    public function statusLogs()
    {
        return $this->hasMany(DevicesStatusLogs::class);
    }

    public function notifications(){
        return $this->hasMany(Notifications::class);
    }

    public function latestStatusLog()
    {
        return $this->hasOne(DevicesStatusLogs::class, 'device_id')->latest('checked_at');
    }
}
