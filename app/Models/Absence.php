<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Absence extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'active',
        'notification_date',
        'cancellation_date',
    ];

    protected $casts = [
        'active' => 'boolean',
        'notification_date' => 'datetime',
        'cancellation_date' => 'datetime',
    ];

    // --- RELATIONS ---

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Inscriptions de rattrapage (makeups) financées par ce crédit d'absence.
     */
    public function replacements(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'replaces_absence_id');
    }

    // --- SCOPES ---

    /**
     * Filtre les crédits d'absence réellement disponibles pour un rattrapage.
     */
    public function scopeAvailableForMakeup(Builder $query): Builder
    {
        return $query->where('active', true)
            ->whereNull('cancellation_date')
            ->whereDoesntHave('replacements', fn ($q) => $q->where('status', 'registered'));
    }

    // --- HELPERS METIER ---

    /**
     * Indique si l'élève a annulé son avis d'absence (il reprend sa place d'origine).
     */
    public function isCancelled(): bool
    {
        return $this->cancellation_date !== null;
    }

    /**
     * Indique si ce crédit d'absence a déjà été consommé pour un rattrapage actif.
     */
    public function isConsumed(): bool
    {
        return $this->replacements()->where('status', 'registered')->exists();
    }

    /**
     * Indique si cette absence donne droit à un crédit de rattrapage disponible.
     */
    public function isAvailableForMakeup(): bool
    {
        return $this->active
            && ! $this->isCancelled()
            && ! $this->isConsumed();
    }

    /**
     * Consomme immédiatement le crédit d'absence lors de la réservation d'un rattrapage.
     */
    public function deactivate(): void
    {
        $this->update([
            'active' => false,
        ]);
    }

    /**
     * Action : L'élève annule sa déclaration d'absence ("Je viens finalement à mon cours d'origine !").
     */
    public function cancelAbsenceNotice(): void
    {
        $this->update([
            'active' => false,
            'cancellation_date' => now(),
        ]);

        // Remet l'inscription d'origine en 'registered'
        $this->enrollment->update(['status' => 'registered']);
    }
}
