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
        $this->normalize('members', 'cell_phone');
        $this->normalize('organizations', 'responsable_cell_phone');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible: the original formatting is lost.
    }

    private function normalize(string $table, string $column): void
    {
        DB::table($table)->select('id', $column)
            ->whereNotNull($column)
            ->orderBy('id')
            ->each(function (object $row) use ($table, $column) {
                $digits = preg_replace('/\D/', '', $row->{$column});

                DB::table($table)->where('id', $row->id)->update([
                    $column => $digits === '' ? null : $digits,
                ]);
            });
    }
};
