<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

public function store(Request $request){
    $validator = Validator::make($request->all(), [
    'place_name' => 'required|string|max:255',
    'location.latitude' => 'required|numeric|between:-90,90',
    'location.longtitude' => 'required|numeric|between:-180,180',
    'user_id'=>'required|exists:users,id',
    'images' => 'nullable|array',
    'images.*' => 'nullable|string',
    'google_map_link' => 'nullable|string|max:255',
    'caption' => 'nullable|string',
    'review' => 'nullable|numeric|min:0|max:5'
]);

}