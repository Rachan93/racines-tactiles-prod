<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('studio_closures', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Vacances de Toussaint", "Fermeture annuelle d'été"
            $table->string('type')->default('school_holiday'); // 'school_holiday', 'studio_closure'
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('type_id')->nullable()->constrained('types')->nullOnDelete(); // null = s'applique à tous, ou [1, 2] pour des types spécifiques
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studio_closures');
    }
};
