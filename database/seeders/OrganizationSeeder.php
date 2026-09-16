<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $provincial = Organization::factory()->provincial()->create([
            'name' => 'Bureau provincial',
            'responsable_first_name' => 'Prov.',
            'responsable_last_name' => '1',
            'responsable_email' => 'provincial@example.com',
            'address' => '1 rue du Parlement, Québec',
            'business_number' => '1000000001',
            'website' => 'https://provincial.example.com',
        ]);

        $region1 = Organization::factory()->regional($provincial)->create([
            'name' => 'Région 1',
            'responsable_first_name' => 'Reg.',
            'responsable_last_name' => '1',
            'responsable_email' => 'regional1@example.com',
            'address' => '10 rue Principale, Région 1',
            'business_number' => '1000000011',
            'website' => 'https://region1.example.com',
        ]);

        $region2 = Organization::factory()->regional($provincial)->create([
            'name' => 'Région 2',
            'responsable_first_name' => 'Reg.',
            'responsable_last_name' => '2',
            'responsable_email' => 'regional2@example.com',
        ]);

        Organization::factory()->regional($provincial)->create([
            'name' => 'Région 3',
            'responsable_first_name' => 'Reg.',
            'responsable_last_name' => '3',
            'responsable_email' => 'regional3@example.com',
        ]);

        $locals = collect(range(1, 3))->map(fn ($i) => Organization::factory()->local($region1)->create([
            'name' => "Organisme local {$i}",
            'responsable_first_name' => 'Loc.',
            'responsable_last_name' => (string) $i,
            'responsable_email' => "local{$i}@example.com",
            'address' => "{$i} rue des Érables, Région 1",
        ]));

        $localInRegion2 = Organization::factory()->local($region2)->create([
            'name' => 'Organisme local (Région 2) 1',
            'responsable_first_name' => 'Loc.',
            'responsable_last_name' => 'R2',
            'responsable_email' => 'local-r2-1@example.com',
        ]);

        $alice = Member::create(['name' => 'Alice Provincial', 'email' => 'alice.provincial@example.com', 'cell_phone' => '514-555-0001']);
        $provincial->memberRoles()->create(['member_id' => $alice->id, 'role' => 'Direction générale']);

        $bruno = Member::create(['name' => 'Bruno Régional', 'email' => 'bruno.regional@example.com', 'cell_phone' => '514-555-0002']);
        $region1->memberRoles()->create(['member_id' => $bruno->id, 'role' => 'Coordination régionale']);

        // Bruno also volunteers locally — one person, two roles, shared name/cell.
        $locals->first()->memberRoles()->create(['member_id' => $bruno->id, 'role' => 'Bénévole']);

        $chantal = Member::create(['name' => 'Chantal Locale', 'email' => 'chantal.locale@example.com', 'cell_phone' => '514-555-0003']);
        $locals->first()->memberRoles()->create(['member_id' => $chantal->id, 'role' => 'Bénévole']);

        $david = Member::create(['name' => 'David Autre-Région', 'email' => 'david.autreregion@example.com', 'cell_phone' => '514-555-0004']);
        $localInRegion2->memberRoles()->create(['member_id' => $david->id, 'role' => 'Bénévole']);
    }
}
