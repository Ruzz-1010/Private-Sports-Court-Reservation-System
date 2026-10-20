<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletingScope;

#[Fillable([
    'reference_number',
    'user_id',
    'court_id',
    'starts_at',
    'ends_at',
    'hourly_rate',
    'total_amount',
    'status',
    'payment_method',
    'payment_deadline',
    'cancellation_reason',
    'expiration_reason',
])]
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'payment_deadline' => 'immutable_datetime',
            'hourly_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => ReservationStatus::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        // Reservation history must outlive the court record. Courts with
        // bookings are archived (soft-deleted) rather than destroyed, so the
        // relation ignores the global soft-delete scope — otherwise every
        // history, report, and receipt row for an archived court renders a
        // null name. New bookings are unaffected: they resolve the court
        // through route model binding and AvailabilityService, both of which
        // still honour the soft-delete scope.
        return $this->belongsTo(Court::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    /**
     * A reservation may accumulate several attempts; only one may settle.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function settledPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('status', 'paid'),
        );
    }

    /**
     * @return HasOne<Receipt, $this>
     */
    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    /**
     * Limit the query to states that still occupy the court schedule.
     *
     * These states mirror the predicate of the
     * reservations_no_overlapping_holds exclusion constraint.
     *
     * @param  Builder<Reservation>  $query
     */
    #[Scope]
    protected function blocking(Builder $query): void
    {
        $query->whereIn('status', ReservationStatus::blockingStatuses());
    }

    /**
     * Limit the query to holds whose payment deadline has already passed.
     *
     * Used by the expiration sweeper and as a defensive sweep before reporting
     * a conflict, because the exclusion constraint cannot use now() in its
     * predicate.
     *
     * @param  Builder<Reservation>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->where('status', ReservationStatus::Pending->value)
            ->whereNotNull('payment_deadline')
            ->where('payment_deadline', '<=', now());
    }

    /**
     * Determine whether the slot is still protected by an active hold.
     */
    public function holdIsActive(): bool
    {
        return $this->status === ReservationStatus::Pending
            && $this->payment_deadline?->isFuture();
    }
}
