<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Inertia\Inertia;
use Inertia\Response;

class GalerieController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Front/Galerie', [
            'images' => GalleryImage::query()->ordered()->get()
                ->map(fn (GalleryImage $image) => $image->toGalleryData()),
        ]);
    }
}
