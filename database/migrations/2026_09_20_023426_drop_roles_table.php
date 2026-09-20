<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Minimum roles are no longer a global, admin-managed list per organization
     * level — each parent organization now defines them for its own children
     * (see the minimum_roles table).
     */
    public function up(): void
    {
        Schema::dropIfExists('roles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('level');
            $table->string('name');
            $table->timestamps();

            $table->unique(['level', 'name']);
        });
    }
};
