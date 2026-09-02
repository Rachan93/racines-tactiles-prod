<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained()->onDelete('restrict');
            $table->morphs('participant');
            $table->tinyInteger('total_lessons');
            $table->tinyInteger('attended_lessons')->default(0);
            $table->decimal('paid_price', 7, 2);
            $table->dateTime('purchase_date');
            $table->dateTime('expiration_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
