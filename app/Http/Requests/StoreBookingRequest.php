<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accommodation_id' => 'required|exists:accommodations,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_rooms' => 'required|integer|min:1|max:10',
            'number_of_guests' => 'required|integer|min:1',
            'services' => 'nullable|array',
            'services.*.service_id' => 'required|exists:extra_services,id',
            'services.*.quantity' => 'required|integer|min:1',
            'special_requests' => 'nullable|string|max:2000',
        ];
    }
}
