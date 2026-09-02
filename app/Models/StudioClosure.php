<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudioClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'start_date',
        'end_date',
        'applies_to_course_types',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'applies_to_course_types' => 'array',
        ];
    }

    // --- SCOPES ---

    /**
     * Fermetures chevauchant une période donnée.
     */
    public function scopeOverlapping(Builder $query, $startDate, $endDate): Builder
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($sub) use ($startDate, $endDate) {
                  $sub->where('start_date', '<=', $startDate)
                      ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Vérifie si une date précise tombe pendant cette fermeture.
     */
    public function coversDate(CarbonInterface|string $date): bool
    {
        $d = is_string($date) ? $date : $date->toDateString();
        return $d >= $this->start_date->toDateString() && $d <= $this->end_date->toDateString();
    }
}
