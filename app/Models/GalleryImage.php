<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GalleryImage extends Model
{
    protected $fillable = ['path', 'description', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public function toGalleryData(): array
    {
        return [
            'id' => $this->id,
            'src' => Storage::disk('public')->url($this->path),
            'description' => $this->description,
            'alt' => $this->description,
        ];
    }
}
