<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiToken extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'token',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function findByPlaintext(string $plaintext): ?self
    {
        return static::where('token', hash('sha256', $plaintext))->first();
    }

    public static function generateFor(int $tenantId, string $name = 'Make.com'): array
    {
        $plaintext = \Illuminate\Support\Str::random(40);

        static::where('tenant_id', $tenantId)->delete();

        static::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'token' => hash('sha256', $plaintext),
        ]);

        return ['plaintext' => $plaintext];
    }
}
