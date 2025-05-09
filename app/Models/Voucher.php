<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'username',
        'password',
        'profile',
        'router_id',
        'limit_mb',
        'status',
        'used_at'
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'limit_mb' => 'integer'
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function profile()
    {
        return $this->belongsTo(HotspotProfile::class, 'profile', 'name');
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isUsed()
    {
        return $this->status === 'used';
    }

    public function isExpired()
    {
        return $this->status === 'expired';
    }

    public function markAsUsed()
    {
        $this->update([
            'status' => 'used',
            'used_at' => now()
        ]);
    }

    public function markAsExpired()
    {
        $this->update([
            'status' => 'expired'
        ]);
    }
}