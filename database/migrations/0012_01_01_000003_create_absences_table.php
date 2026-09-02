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
         Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->boolean('active')->default(true);
            $table->datetime('notification_date');
            $table->datetime('cancellation_date')->nullable();
            $table->timestamps();

            $table->index(['enrollment_id', 'active']);

        });

        // pour éviter les erreurs de clé étrangère (vu que enrollments et absences se réfèrent mutuellement)
        Schema::table('enrollments', function (Blueprint $table) {
        $table->foreignId('replaces_absence_id')
              ->nullable()
              ->constrained('absences')
              ->nullOnDelete();
    });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
// pour dégager replaces_absence_id (vu que c'est créé dans cette migration)
         Schema::table('enrollments', function (Blueprint $table) {
        if (Schema::hasColumn('enrollments', 'replaces_absence_id')) {
            $table->dropForeign(['replaces_absence_id']);
            $table->dropColumn('replaces_absence_id');
        }
    });
        Schema::dropIfExists('absences');
    }
};
