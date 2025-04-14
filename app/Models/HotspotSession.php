<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotspotSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotspot_user_id',
        'session_id',
        'mac_address',
        'ip_address',
        'server',
        'uptime',
        'bytes_in',
        'bytes_out',
        'packets_in',
        'packets_out',
        'login_by',
        'session_time_left',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'bytes_in' => 'integer',
        'bytes_out' => 'integer',
        'packets_in' => 'integer',
        'packets_out' => 'integer',
    ];

    public function hotspotUser()
    {
        return $this->belongsTo(HotspotUser::class);
    }
} 