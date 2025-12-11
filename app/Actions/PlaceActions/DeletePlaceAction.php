<?php

namespace App\Actions\PlaceActions;

use App\Models\Place;
use Illuminate\Support\Facades\Storage;

class DeletePlaceAction
{
    public function handle(Place $place): void
    {
        // Delete associated images
        if ($place->images) {
            foreach ($place->images as $imageUrl) {
                $parsedUrl = parse_url($imageUrl);
                if (isset($parsedUrl['path'])) {
                    $path = ltrim($parsedUrl['path'], '/');
                    if (strpos($path, 'storage/') === 0) {
                        $path = substr($path, 8);
                    }
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
        }

        $place->delete();
    }
}
