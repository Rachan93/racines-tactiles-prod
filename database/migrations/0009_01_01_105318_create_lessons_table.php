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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            $table->date('date');

            $table->foreignId('override_instructor_id')->nullable()->constrained('instructors', 'id')->onDelete('restrict');
            $table->time('override_start_time')->nullable();
            $table->time('override_end_time')->nullable();
            $table->tinyInteger('override_spots_max_handbuilding')->nullable();
            $table->tinyInteger('override_spots_max_wheel')->nullable();
            $table->decimal('override_price', 7, 2)->nullable();

            $table->boolean('is_cancelled')->default(false);
            $table->text('cancellation_reason')->nullable();

            $table->boolean('is_overridden')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
