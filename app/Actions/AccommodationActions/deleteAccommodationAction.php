<?php

namespace App\Actions\AccommodationActions;

use App\Models\Accommodation;
use Illuminate\Support\Facades\Storage;

class DeleteAccommodationAction
{
    public function handle(Accommodation $accommodation): void
    {
        // Delete associated images
        if ($accommodation->images) {
            foreach ($accommodation->images as $imageUrl) {
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

        $accommodation->delete();
    }
}
