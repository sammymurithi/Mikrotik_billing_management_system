<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    protected $fillable = [
        'name',
        'ip_address',
        'username',
        'password',
        'port',
        'comment'
    ];

    public function users(): HasMany
    {
        return $this->hasMany(HotspotUser::class);
    }

    public function hotspotUsers()
    {
        return $this->hasMany(HotspotUser::class);
    }
}
