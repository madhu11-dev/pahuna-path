<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH') || $this->input('_method') === 'PUT';

        return [
            'room_name' => 'required|string|max:255',
            'room_type' => 'required|in:single,double,suite,family,dormitory',
            'has_ac' => 'required|in:0,1,true,false',
            'capacity' => 'required|integer|min:1|max:20',
            'total_rooms' => 'required|integer|min:1|max:500',
            'base_price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }
}
