<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotspotProfile extends Model
{
    protected $fillable = [
        'mikrotik_id',
        'router_id',
        'name',
        'category',
        'rate_limit',
        'shared_users',
        'mac_cookie_timeout',
        'keepalive_timeout',
        'session_timeout',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected $attributes = [
        'price' => 0.00,
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = $value ?? 0.00;
    }
}