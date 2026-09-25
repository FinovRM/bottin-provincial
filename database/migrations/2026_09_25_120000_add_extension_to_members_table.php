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
        Schema::table('members', function (Blueprint $table) {
            $table->string('extension')->nullable()->after('cell_phone');
        });

        // Digits typed after the 10-digit number were an extension.
        DB::table('members')->select('id', 'cell_phone')
            ->whereRaw('LENGTH(cell_phone) > 10')
            ->orderBy('id')
            ->each(function (object $row) {
                DB::table('members')->where('id', $row->id)->update([
                    'cell_phone' => substr($row->cell_phone, 0, 10),
                    'extension' => substr($row->cell_phone, 10),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('members')->select('id', 'cell_phone', 'extension')
            ->whereNotNull('extension')
            ->orderBy('id')
            ->each(function (object $row) {
                DB::table('members')->where('id', $row->id)->update([
                    'cell_phone' => $row->cell_phone.$row->extension,
                ]);
            });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('extension');
        });
    }
};
