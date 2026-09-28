<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlacementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('placements.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:255', 'unique:placements,code,'.$this->route('placement')->id],
            'floor_id' => ['sometimes', 'integer', 'exists:floors,id'],
            'x' => ['nullable', 'numeric'],
            'y' => ['nullable', 'numeric'],
            'location_description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
