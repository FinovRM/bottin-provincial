<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The region choice is now a search that only narrows the profile page; it is no longer saved.
    public function up(): void
    {
        DB::table('personal_filters')->update(['region_ids' => json_encode([])]);
    }

    public function down(): void
    {
        // Cleared region selections can't be restored.
    }
};
