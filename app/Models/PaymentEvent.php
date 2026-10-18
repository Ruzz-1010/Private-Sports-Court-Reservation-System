<?php

namespace App\Models;

use Database\Factories\PaymentEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Raw record of every provider callback received.
 *
 * The unique provider_event_id column is the idempotency guard: a repeated
 * delivery cannot produce a second payment or receipt.
 */
#[Fillable([
    'provider',
    'provider_event_id',
    'event_type',
    'processing_status',
    'received_at',
    'payload',
    'notes',
])]
class PaymentEvent extends Model
{
    /** @use HasFactory<PaymentEventFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'immutable_datetime',
            'payload' => 'array',
        ];
    }
}
