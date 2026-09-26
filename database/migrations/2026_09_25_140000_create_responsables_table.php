<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An organization can now have several responsables. Each organization's
     * single responsable moves to this table, and the "Responsable du bottin"
     * member roles that duplicated them are removed.
     */
    public function up(): void
    {
        Schema::create('responsables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->index();
            $table->string('cell_phone')->nullable();
            $table->string('extension')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'email']);
        });

        $now = now();

        DB::table('organizations')->orderBy('id')->each(function (object $organization) use ($now) {
            DB::table('responsables')->insert([
                'organization_id' => $organization->id,
                'name' => $organization->responsable_name,
                'email' => $organization->responsable_email,
                'cell_phone' => $organization->responsable_cell_phone,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        $memberIds = DB::table('member_roles')->where('role', 'Responsable du bottin')->pluck('member_id')->unique();
        DB::table('member_roles')->where('role', 'Responsable du bottin')->delete();

        // A person left without any role is no longer a member.
        $orphanIds = DB::table('members')->whereIn('id', $memberIds)
            ->whereNotExists(fn ($roles) => $roles->select(DB::raw(1))->from('member_roles')->whereColumn('member_roles.member_id', 'members.id'))
            ->pluck('id');
        DB::table('personal_filters')->where('filterable_type', 'App\\Models\\Member')->whereIn('filterable_id', $orphanIds)->delete();
        DB::table('members')->whereIn('id', $orphanIds)->delete();

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['responsable_email']);
            $table->dropColumn(['responsable_name', 'responsable_email', 'responsable_cell_phone']);
        });
    }

    /**
     * Each organization gets back its first responsable. The removed member
     * roles are not restored.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('responsable_name')->default('');
            $table->string('responsable_email')->default('')->index();
            $table->string('responsable_cell_phone')->nullable();
        });

        DB::table('responsables')->orderBy('id')->get()->unique('organization_id')->each(function (object $responsable) {
            DB::table('organizations')->where('id', $responsable->organization_id)->update([
                'responsable_name' => $responsable->name,
                'responsable_email' => $responsable->email,
                'responsable_cell_phone' => $responsable->cell_phone,
            ]);
        });

        Schema::drop('responsables');
    }
};
