<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class DeclarationVersion extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'declaration_id',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(Declaration::class);
    }

    public function setContentAttribute($value): void
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        $this->attributes['content'] = Crypt::encryptString($value);
    }

    public function getContentAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }
        try {
            $decrypted = Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return [];
        }
        $data = json_decode($decrypted, true);
        return is_array($data) ? $data : [];
    }
}
