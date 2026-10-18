<?php

namespace App\Models;

use Database\Factories\ReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable proof of a verified payment.
 *
 * Every displayed value is copied at issue time so later edits to the court,
 * reservation, or rate can never rewrite an issued receipt.
 */
#[Fillable([
    'receipt_number',
    'reservation_id',
    'payment_id',
    'reservation_reference',
    'customer_name',
    'customer_email',
    'court_name',
    'court_location',
    'reservation_date',
    'starts_at',
    'ends_at',
    'duration_minutes',
    'payment_method',
    'payment_status',
    'amount_paid',
    'currency',
    'transaction_date',
])]
class Receipt extends Model
{
    /** @use HasFactory<ReceiptFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'transaction_date' => 'immutable_datetime',
            'duration_minutes' => 'integer',
            'amount_paid' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
