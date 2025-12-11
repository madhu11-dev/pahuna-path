<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_name' => 'sometimes|required|string|max:255',
            'room_type' => 'sometimes|required|in:standard,deluxe,suite,family,single,double,dormitory',
            'has_ac' => 'sometimes|required|in:0,1,true,false',
            'description' => 'sometimes|nullable|string|max:2000',
            'capacity' => 'sometimes|required|integer|min:1|max:20',
            'total_rooms' => 'sometimes|required|integer|min:1|max:500',
            'base_price' => 'sometimes|required|numeric|min:0',
            'images' => 'sometimes|nullable|array|max:10',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }
}
