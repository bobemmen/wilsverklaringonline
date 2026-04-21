<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeclarationShare extends Model
{
    use HasUlids;

    protected $fillable = [
        'declaration_id',
        'email',
        'name',
        'role',
        'share_token',
        'accepted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(Declaration::class);
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at);
    }
}
