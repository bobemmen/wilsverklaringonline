<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'declaration_id',
        'accessor_type',
        'accessor_identifier',
        'ip_address',
        'user_agent',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(Declaration::class);
    }
}
