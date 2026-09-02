<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'participant_type',
        'type_id',
        'total_lessons',
        'attended_lessons',
        'paid_price',
        'purchase_date',
        'expiration_date',
        'is_active',
    ];

    protected $casts = [
        'total_lessons' => 'integer',
        'attended_lessons' => 'integer',
        'paid_price' => 'decimal:2',
        'purchase_date' => 'datetime',
        'expiration_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    // --- RELATIONS ---

    /**
     * Le participant (Polymorphe : User ou Attendee)
     */
    public function participant(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Le type de cours du module (Collectif, Stage, Privé)
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * Les inscriptions aux leçons rattachées à ce module
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Accès direct aux absences générées par ce module
     */
    public function absences(): HasManyThrough
    {
        return $this->hasManyThrough(
            Absence::class,
            Enrollment::class,
            'module_id',     // Clé étrangère sur enrollments
            'enrollment_id', // Clé étrangère sur absences
            'id',            // Clé locale sur modules
            'id'             // Clé locale sur enrollments
        );
    }

    // --- ACCESSEURS ET HELPERS METIER ---

    /**
     * Quota de rattrapages autorisés (2 rattrapages par tranche de 10 cours)
     * Ex: 10 cours -> 2 max | 20 cours -> 4 max | 30 cours -> 6 max
     */
    public function getMaxMakeupsAllowedAttribute(): int
    {
        if ($this->total_lessons <= 0) {
            return 0;
        }

        return (int) floor(($this->total_lessons / 10) * 2);
    }

    /**
     * Nombre de rattrapages déjà réservés/utilisés sur ce module
     */
    public function getMakeupsUsedCountAttribute(): int
    {
        return $this->enrollments()
            ->where('enrollment_type', 'makeup')
            ->whereIn('status', ['registered', 'absent'])
            ->count();
    }
    /**
     * Nombre de crédits de rattrapage encore disponibles sur le quota du module
     */
    public function getRemainingMakeupsAttribute(): int
    {
        return max(0, $this->max_makeups_allowed - $this->makeups_used_count);
    }

    /**
     * Indique si le module est actif et dispose encore de quota pour poser un rattrapage
     */
    public function canBookMakeup(): bool
    {
        return $this->is_active && $this->remaining_makeups > 0;
    }
}
