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
  Schema::create('courses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('type_id')->constrained()->onDelete('restrict'); // Collectif, Stage, Privé
    $table->foreignId('default_instructor_id')->constrained('instructors', 'id')->onDelete('restrict');

    // Nom & Catégorie
    $table->string('name'); // ex: "Moonjars avec Maitre Choi Woo Cheal (Tamlayo) et Wise.Pottery"
    $table->string('name_en')->nullable(); // ex: "Moonjars with Master Choi Woo Cheal (Tamlayo) and Wise.Pottery "
    $table->enum('sub_type', ['wheel', 'themed', 'external', 'one-off'])->nullable();


    // Contenu Bilingue (Optionnel pour les stages / masterclasses)
    $table->string('subtitle')->nullable(); // "L’art du Buncheong & de l’Inhwamun"
    $table->string('subtitle_en')->nullable(); // "The art of Buncheong & Inhwamun"
    $table->text('description')->nullable(); // Texte FR
    $table->text('description_en')->nullable(); // Texte EN
    $table->text('practical_info')->nullable(); // "Prérequis : 2kg d'argile, lunch inclus..."
    $table->text('practical_info_en')->nullable(); // "Prerequisite: 2kg clay, lunch included..."
    $table->string('cover_image')->nullable(); // Photo d'affiche

    // Dates & Récurrence
    $table->date('first_lesson_date');
    $table->date('end_date'); // n'est pas forcément la date de la dernière séance
    $table->time('default_start_time');
    $table->time('default_end_time');
    $table->unsignedTinyInteger('frequency')->default(7); // 1 = quotidien (stages), 7 = hebdo (collectifs)

    // Capacités & Tarifs
    $table->tinyInteger('default_spots_max_handbuilding')->default(0);
    $table->tinyInteger('default_spots_max_wheel')->default(0);
    $table->decimal('default_price', 7, 2);

    // Modalités & Statut
  //  $table->string('booking_mode')->default('online'); // 'online' (site) ou 'email' (candidature)
    $table->boolean('is_active')->default(true); // Masqué du calendrier si false
    $table->boolean('is_featured')->default(false); // Mis en avant dans la page Stages

    $table->timestamps();
});
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
