<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Declaration extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'access_token',
        'access_token_expires_at',
        'notification_email',
        'current_version_id',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'access_token_expires_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DeclarationVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DeclarationVersion::class, 'current_version_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DeclarationShare::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function tokenIsActive(): bool
    {
        if (empty($this->access_token)) {
            return false;
        }
        if ($this->access_token_expires_at && $this->access_token_expires_at->isPast()) {
            return false;
        }
        return true;
    }
}
