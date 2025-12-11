<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MergePlacesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placeIds' => 'required|array|min:2',
            'placeIds.*' => 'required|integer|exists:places,id',
            'mergeData' => 'required|array',
            'mergeData.selectedPlaceName' => 'required|string|max:255',
            'mergeData.selectedDescription' => 'required|string',
            'mergeData.selectedImages' => 'required|array|min:1',
            'mergeData.selectedLocation' => 'required|string',
            'mergeData.selectedLatitude' => 'nullable|numeric',
            'mergeData.selectedLongitude' => 'nullable|numeric',
            'mergeData.userId' => 'required|integer|exists:users,id',
        ];
    }
}
