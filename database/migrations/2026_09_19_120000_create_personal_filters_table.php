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
        Schema::create('personal_filters', function (Blueprint $table) {
            $table->id();
            $table->morphs('filterable');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('region_ids')->nullable();
            $table->json('local_ids')->nullable();
            $table->json('roles')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_filters');
    }
};
