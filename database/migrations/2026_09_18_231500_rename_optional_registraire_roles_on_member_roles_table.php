<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('member_roles')
            ->whereIn('role', ['Registraire 2 (Facultatif)', 'Registraire 3 (Facultatif)'])
            ->update(['role' => 'Registraire']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible: the original distinction between roles is lost.
    }
};
