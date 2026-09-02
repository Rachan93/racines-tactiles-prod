<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('restrict');
            $table->foreignId('module_id')->constrained()->onDelete('cascade');

            $table->enum('status', ['registered', 'absent', 'cancelled'])->default('registered');

            $table->enum('enrollment_type', ['regular', 'replacement', 'makeup'])->default('regular');

            $table->enum('spot_type', ['wheel', 'handbuilding'])->default('wheel');

            // replaces_absence_id est dans la migration des absences pour éviter les erreurs de clé étrangère

            $table->datetime('cancellation_date')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
