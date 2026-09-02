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
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->dateTime('date');
            $table->decimal('height', total: 4, places: 1)->nullable();
            $table->decimal('surface', total: 4, places: 3)->nullable(); // faut voir les unités utilisées
            $table->string('firing')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('price', total: 7, places: 2);
            $table->foreignId('user_id')->default(1)->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
