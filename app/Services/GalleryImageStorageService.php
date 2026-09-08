<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class GalleryImageStorageService
{
    public function store(UploadedFile $image): string
    {
        try {
            $path = $image->store('gallery', 'public');

            if (! is_string($path) || $path === '') {
                throw new RuntimeException('Impossible d’écrire dans le dossier gallery.');
            }

            return $path;
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'image' => 'Impossible d’enregistrer l’image. Veuillez réessayer.',
            ]);
        }
    }

    public function deleteOrReport(string $path): void
    {
        // Le disque public retourne false sur certains échecs (throw => false).
        try {
            if (! Storage::disk('public')->delete($path)) {
                throw new RuntimeException('Impossible de supprimer une image de galerie : '.$path);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
