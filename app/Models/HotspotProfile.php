<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotspotProfile extends Model
{
    protected $fillable = [
        'mikrotik_id',
        'router_id',
        'name',
        'rate_limit',
        'shared_users',
        'mac_cookie_timeout',
        'keepalive_timeout',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}