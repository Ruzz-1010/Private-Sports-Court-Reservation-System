<?php

namespace App\Http\Requests\Reservations;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Administrators manage reservations through the admin area.
        return ! $this->user()?->isAdmin();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'court_id' => ['required', 'integer', Rule::exists('courts', 'id')->where('is_active', true)],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'duration_minutes' => [
                'required', 'integer',
                'min:'.config('reservation.min_duration_minutes'),
                'max:'.config('reservation.max_duration_minutes'),
            ],
            'payment_method' => ['required', Rule::in([
                PaymentMethod::Online->value,
                PaymentMethod::OnSite->value,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'court_id.exists' => 'That court is not available for booking.',
            'date.required' => 'Please choose a date.',
            'time.required' => 'Please choose a start time.',
            'duration_minutes.min' => 'The minimum booking duration is '.config('reservation.min_duration_minutes').' minutes.',
            'duration_minutes.max' => 'The maximum booking duration is '.config('reservation.max_duration_minutes').' minutes.',
            'payment_method.in' => 'Please choose a valid payment method.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $duration = $this->input('duration_minutes');

        if (is_numeric($duration)) {
            $this->merge(['duration_minutes' => (int) $duration]);
        }
    }
}
