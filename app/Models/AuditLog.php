<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra um evento na trilha de auditoria.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public static function record(
        string $action,
        Model $auditable,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
    ): self {
        $request = request();

        return static::create([
            'user_id' => $userId ?? $request?->user()?->id,
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => (int) $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
            'created_at' => now(),
        ]);
    }
}
