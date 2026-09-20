<?php

use App\Enums\OrganizationGroup;
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
        Schema::table('allowed_roles', function (Blueprint $table) {
            $table->string('group')->default(OrganizationGroup::Organisation->value)->after('organization_id');
        });

        Schema::table('allowed_roles', function (Blueprint $table) {
            // MySQL needs some index covering organization_id at all times to
            // satisfy the foreign key, so the new one goes in before the old
            // one comes out.
            $table->unique(['organization_id', 'group', 'name']);
        });

        Schema::table('allowed_roles', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allowed_roles', function (Blueprint $table) {
            $table->unique(['organization_id', 'name']);
        });

        Schema::table('allowed_roles', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'group', 'name']);
            $table->dropColumn('group');
        });
    }
};
