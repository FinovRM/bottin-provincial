<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->renameColumn('responsable_last_name', 'responsable_name');
        });

        DB::table('organizations')->select('id', 'responsable_first_name', 'responsable_name')
            ->orderBy('id')
            ->each(function (object $organization) {
                DB::table('organizations')->where('id', $organization->id)->update([
                    'responsable_name' => trim("{$organization->responsable_first_name} {$organization->responsable_name}"),
                ]);
            });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('responsable_first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('responsable_first_name')->nullable()->after('name');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->renameColumn('responsable_name', 'responsable_last_name');
        });
    }
};
