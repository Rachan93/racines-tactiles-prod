<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_id',
        'default_instructor_id',
        'name',
        'name_en',
        'sub_type',
        'subtitle',
        'subtitle_en',
        'description',
        'description_en',
        'practical_info',
        'practical_info_en',
        'cover_image',
        'first_lesson_date',
        'end_date',
        'default_start_time',
        'default_end_time',
        'frequency',
        'default_spots_max_handbuilding',
        'default_spots_max_wheel',
        'default_price',
        'booking_mode',
        'is_active',
        'is_featured',
    ];

    /**
     * Casts des attributs du modèle (Laravel 11).
     */
    protected function casts(): array
    {
        return [
            'first_lesson_date' => 'date',
            'end_date' => 'date',
            'frequency' => 'integer',
            'default_spots_max_handbuilding' => 'integer',
            'default_spots_max_wheel' => 'integer',
            'default_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    // --- SCOPES METIER (Gestion temporelle et statut) ---

    /**
     * Scope : Cours activés administrativement (publiés et ouverts).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Cours en cours ou futurs (non terminés, ou ayant des séances reportées à venir).
     */
    public function scopeUpcomingOrOngoing(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where(function (Builder $q) use ($today) {
            $q->where('end_date', '>=', $today)
              ->orWhereHas('lessons', fn ($lessonQuery) => $lessonQuery->where('date', '>=', $today));
        });
    }

    /**
     * Scope : Cours passés / terminés (la date de fin ET toutes les séances sont dans le passé).
     */
    public function scopePast(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('end_date', '<', $today)
                     ->whereDoesntHave('lessons', fn ($lessonQuery) => $lessonQuery->where('date', '>=', $today));
    }

    /**
     * Scope : Cours disponibles pour les élèves (Actifs ET non terminés).
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()->upcomingOrOngoing();
    }

    // --- RELATIONS ---

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'default_instructor_id');
    }

    /**
     * Alias pour la relation instructor
     */
    public function defaultInstructor(): BelongsTo
    {
        return $this->instructor();
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

}
