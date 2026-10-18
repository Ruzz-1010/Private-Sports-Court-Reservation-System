<?php

namespace App\Http\Requests\Admin;

use App\Services\Courts\UploadCourtImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $courtId = $this->route('court')?->getKey();

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('courts', 'name')->ignore($courtId),
            ],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            ...UploadCourtImage::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A court with this name already exists.',
            'hourly_rate.required' => 'Please enter an hourly rate.',
            'hourly_rate.numeric' => 'The hourly rate must be a number.',
            ...UploadCourtImage::messages(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }

        if ($this->has('location')) {
            $this->merge(['location' => trim((string) $this->input('location'))]);
        }

        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
