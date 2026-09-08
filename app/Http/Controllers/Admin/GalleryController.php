<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryImageRequest;
use App\Http\Requests\GalleryOrderRequest;
use App\Models\GalleryImage;
use App\Services\GalleryImageStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class GalleryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Gallery/Index', [
            'images' => GalleryImage::query()->ordered()->get()
                ->map(fn (GalleryImage $image) => $image->toGalleryData()),
        ]);
    }

    public function store(GalleryImageRequest $request, GalleryImageStorageService $storage): RedirectResponse
    {
        $path = $storage->store($request->file('image'));

        try {
            DB::transaction(function () use ($request, $path) {
                $images = GalleryImage::query()->orderBy('id')->lockForUpdate()->get();

                GalleryImage::create([
                    'path' => $path,
                    'description' => $request->validated('description'),
                    'position' => ((int) $images->max('position')) + 1,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            $storage->deleteOrReport($path);
            throw $exception;
        }

        return to_route('gallery-images.index');
    }

    public function update(
        GalleryImageRequest $request,
        GalleryImage $galleryImage,
        GalleryImageStorageService $storage,
    ): RedirectResponse {
        $newPath = $request->hasFile('image') ? $storage->store($request->file('image')) : null;

        try {
            $oldPath = DB::transaction(function () use ($request, $galleryImage, $newPath) {
                $image = GalleryImage::query()->lockForUpdate()->findOrFail($galleryImage->id);
                $oldPath = $image->path;
                $image->update([
                    'description' => $request->validated('description'),
                    ...($newPath !== null ? ['path' => $newPath] : []),
                ]);

                return $oldPath;
            }, 3);
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                $storage->deleteOrReport($newPath);
            }
            throw $exception;
        }

        if ($newPath !== null && $oldPath !== $newPath) {
            $storage->deleteOrReport($oldPath);
        }

        return to_route('gallery-images.index');
    }

    public function reorder(GalleryOrderRequest $request): RedirectResponse
    {
        $ids = array_map('intval', array_values($request->validated('ids')));
        $originalIds = array_map('intval', array_values($request->validated('original_ids')));

        DB::transaction(function () use ($ids, $originalIds) {
            $images = GalleryImage::query()->orderBy('id')->lockForUpdate()->get();
            $currentIds = $images->sortBy([
                ['position', 'asc'], ['id', 'asc'],
            ])->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

            if ($currentIds !== $originalIds) {
                throw ValidationException::withMessages([
                    'ids' => 'La galerie a changé depuis son ouverture. Rechargez les images avant de les reclasser.',
                ]);
            }

            $expected = $currentIds;
            $received = $ids;
            sort($expected);
            sort($received);

            if ($expected !== $received) {
                throw ValidationException::withMessages([
                    'ids' => 'Le classement doit contenir exactement toutes les images de la galerie.',
                ]);
            }

            foreach ($ids as $position => $id) {
                GalleryImage::query()->whereKey($id)->update(['position' => $position + 1]);
            }
        }, 3);

        return to_route('gallery-images.index');
    }

    public function destroy(
        Request $request,
        GalleryImage $galleryImage,
        GalleryImageStorageService $storage,
    ): RedirectResponse {
        $request->validate(
            ['confirmed' => ['accepted']],
            ['confirmed.accepted' => 'Confirmez la suppression de cette image.'],
        );

        $path = DB::transaction(function () use ($galleryImage) {
            $image = GalleryImage::query()->lockForUpdate()->findOrFail($galleryImage->id);
            $path = $image->path;
            $image->delete();

            return $path;
        }, 3);

        $storage->deleteOrReport($path);

        return to_route('gallery-images.index');
    }
}
