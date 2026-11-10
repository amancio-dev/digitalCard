<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'cep',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedLine(): string
    {
        $parts = array_filter([
            trim($this->street.', '.$this->number),
            $this->complement,
            $this->neighborhood,
            trim($this->city.' - '.$this->state),
            'CEP '.$this->cep,
        ]);

        return implode(' — ', $parts);
    }
}
