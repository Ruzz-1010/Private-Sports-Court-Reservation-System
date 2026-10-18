<?php

namespace App\Models;

use Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'location', 'description', 'image_path', 'hourly_rate', 'is_active'])]
class Court extends Model
{
    /** @use HasFactory<CourtFactory> */
    use HasFactory, SoftDeletes;

    public const IMAGE_DISK = 'public';

    public const IMAGE_DIRECTORY = 'courts';

    /**
     * Public URL for the court photo, or null when none has been uploaded.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path
            ? asset('storage/'.$this->image_path)
            : null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return HasMany<CourtBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(CourtBlock::class);
    }

    /**
     * Limit the query to courts that can accept new reservations.
     *
     * @param  Builder<Court>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
