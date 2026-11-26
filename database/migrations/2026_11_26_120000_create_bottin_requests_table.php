<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bottin_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // modification | retrait
            $table->string('name');
            $table->string('email');
            $table->json('roles');
            $table->string('new_email')->nullable();
            $table->string('new_cell_phone')->nullable();
            $table->string('new_extension')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bottin_requests');
    }
};
