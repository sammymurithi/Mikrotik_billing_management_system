<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotspotProfile extends Model
{
    protected $fillable = [
        'name',
        'rate_limit',
        'shared_users',
        'comment'
    ];

    public function users(): HasMany
    {
        return $this->hasMany(HotspotUser::class);
    }
}
