<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'module_id',
        'status',
        'enrollment_type',
        'spot_type',
        'replaces_absence_id',
        'cancellation_date',
    ];

    protected $casts = [
        'cancellation_date' => 'datetime',
    ];

    // --- RELATIONS ---

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function replacesAbsence(): BelongsTo
    {
        return $this->belongsTo(Absence::class, 'replaces_absence_id');
    }

    // --- HELPERS METIER ---

    /**
     * Indique s'il s'agit d'une inscription régulière (module complet)
     */
    public function isRegular(): bool
    {
        return $this->enrollment_type === 'regular';
    }

    /**
     * Indique s'il s'agit d'un rattrapage
     */
    public function isMakeup(): bool
    {
        return $this->enrollment_type === 'makeup';
    }

    /**
     * Indique si l'élève est inscrit et actif
     */
    public function isRegistered(): bool
    {
        return $this->status === 'registered';
    }

    /**
     * Indique si l'élève a été marqué absent sur ce cours
     */
    public function isAbsent(): bool
    {
        return $this->status === 'absent';
    }
}
