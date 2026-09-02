<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'date',
        'override_instructor_id',
        'override_start_time',
        'override_end_time',
        'override_spots_max_handbuilding',
        'override_spots_max_wheel',
        'override_price',
        'is_cancelled',
        'cancellation_reason',
        'is_overridden',
    ];

    protected $casts = [
        'date' => 'date',
        'is_cancelled' => 'boolean',
        'is_overridden' => 'boolean',
        'override_price' => 'decimal:2',
    ];

    // --- RELATIONS ---

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function overrideInstructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'override_instructor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Accès direct aux absences rattachées aux inscriptions de cette leçon.
     * Permet le suivi d'historique (notification_date, cancellation_date, active).
     */
    public function absences(): HasManyThrough
    {
        return $this->hasManyThrough(
            Absence::class,
            Enrollment::class,
            'lesson_id',      // Clé étrangère sur la table enrollments
            'enrollment_id',  // Clé étrangère sur la table absences
            'id',             // Clé locale sur la table lessons
            'id'              // Clé locale sur la table enrollments
        );
    }

    // --- ACCESSEURS METIER (Interrupteur is_overridden ON/OFF) ---

    public function getEffectiveSpotsMaxHandbuildingAttribute(): int
    {
        if ($this->is_overridden && $this->override_spots_max_handbuilding !== null) {
            return $this->override_spots_max_handbuilding;
        }

        return $this->course?->default_spots_max_handbuilding ?? 0;
    }

    public function getEffectiveSpotsMaxWheelAttribute(): int
    {
        if ($this->is_overridden && $this->override_spots_max_wheel !== null) {
            return $this->override_spots_max_wheel;
        }

        return $this->course?->default_spots_max_wheel ?? 0;
    }

    public function getEffectivePriceAttribute(): float
    {
        if ($this->is_overridden && $this->override_price !== null) {
            return (float) $this->override_price;
        }

        return (float) ($this->course?->default_price ?? 0.00);
    }

    public function getEffectiveStartTimeAttribute(): string
    {
        if ($this->is_overridden && $this->override_start_time !== null) {
            return $this->override_start_time;
        }

        return $this->course?->default_start_time ?? '00:00:00';
    }

    public function getEffectiveEndTimeAttribute(): string
    {
        if ($this->is_overridden && $this->override_end_time !== null) {
            return $this->override_end_time;
        }

        return $this->course?->default_end_time ?? '00:00:00';
    }

    public function getEffectiveInstructorAttribute(): ?Instructor
    {
        if ($this->is_overridden && $this->override_instructor_id !== null) {
            return $this->overrideInstructor;
        }

        return $this->course?->defaultInstructor;
    }
}
