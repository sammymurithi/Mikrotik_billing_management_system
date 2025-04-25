<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotspotUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'mikrotik_id',
        'username',
        'password',
        'mac_address',
        'profile_name',
        'router_id',
        'disabled',
        'expires_at',
    ];

    protected $casts = [
        'disabled' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(HotspotSession::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HotspotProfile::class, 'profile_name', 'name');
    }
}
