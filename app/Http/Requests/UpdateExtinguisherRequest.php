<?php

namespace App\Http\Requests;

use App\ExtinguisherStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateExtinguisherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('extinguishers.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'placement_id' => ['nullable', 'integer', 'exists:placements,id'],
            'serial_number' => ['sometimes', 'string', 'max:255', 'unique:extinguishers,serial_number,'.$this->route('extinguisher')->id],
            'extinguisher_type_id' => ['sometimes', 'integer', 'exists:extinguisher_types,id'],
            'extinguisher_brand_id' => ['nullable', 'integer', 'exists:extinguisher_brands,id'],
            'supplier_id' => ['sometimes', 'integer', 'exists:extinguisher_suppliers,id'],
            'capacity' => ['sometimes', 'numeric', 'min:0'],
            'capacity_unit' => ['sometimes', 'in:L,kg'],
            'extinguishing_capacity' => ['nullable', 'regex:/^(?:\d+[A-Za-z]+|[A-Za-z]+)(?:-(?:\d+[A-Za-z]+|[A-Za-z]+)){0,2}$/'],
            'maintenance_seal' => ['nullable', 'string', 'max:255', 'unique:extinguishers,maintenance_seal,'.$this->route('extinguisher')->id],
            'status' => ['sometimes', new Enum(ExtinguisherStatus::class)],
            'next_refill_date' => ['sometimes', 'date_format:Y-m-d'],
            'next_maintenance_year' => ['sometimes', 'integer', 'min:2000', 'max:2200'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
