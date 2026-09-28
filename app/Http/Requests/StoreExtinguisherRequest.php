<?php

namespace App\Http\Requests;

use App\ExtinguisherStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreExtinguisherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('extinguishers.create');
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
            'serial_number' => ['required', 'string', 'max:255', 'unique:extinguishers,serial_number'],
            'extinguisher_type_id' => ['required', 'integer', 'exists:extinguisher_types,id'],
            'extinguisher_brand_id' => ['nullable', 'integer', 'exists:extinguisher_brands,id'],
            'supplier_id' => ['required', 'integer', 'exists:extinguisher_suppliers,id'],
            'capacity' => ['required', 'numeric', 'min:0'],
            'capacity_unit' => ['required', 'in:L,kg'],
            'extinguishing_capacity' => ['nullable', 'regex:/^(?:\d+[A-Za-z]+|[A-Za-z]+)(?:-(?:\d+[A-Za-z]+|[A-Za-z]+)){0,2}$/'],
            'maintenance_seal' => ['nullable', 'string', 'max:255', 'unique:extinguishers,maintenance_seal'],
            'status' => ['sometimes', new Enum(ExtinguisherStatus::class)],
            'next_refill_date' => ['required', 'date_format:Y-m-d'],
            'next_maintenance_year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
