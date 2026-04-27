<?php

namespace App\Models;

use App\Traits\AuditableWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use AuditableWithUser;
    protected $fillable = [
        'name',
        'subscription_active',
        'subscription_ends_at',
    ];

    protected $casts = [
        'subscription_active' => 'boolean',
        'subscription_ends_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}