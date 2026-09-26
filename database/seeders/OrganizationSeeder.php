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
        $provincial = Organization::factory()->provincial()->withResponsable([
            'name' => 'Prov. 1',
            'email' => 'provincial@example.com',
        ])->create([
            'name' => 'Bureau provincial',
            'address' => '1 rue du Parlement, Québec',
            'business_number' => '1000000001',
            'website' => 'https://provincial.example.com',
        ]);

        $region1 = Organization::factory()->regional($provincial)->withResponsable([
            'name' => 'Reg. 1',
            'email' => 'regional1@example.com',
        ])->create([
            'name' => 'Région 1',
            'address' => '10 rue Principale, Région 1',
            'business_number' => '1000000011',
            'website' => 'https://region1.example.com',
        ]);

        $region2 = Organization::factory()->regional($provincial)->withResponsable([
            'name' => 'Reg. 2',
            'email' => 'regional2@example.com',
        ])->create(['name' => 'Région 2']);

        Organization::factory()->regional($provincial)->withResponsable([
            'name' => 'Reg. 3',
            'email' => 'regional3@example.com',
        ])->create(['name' => 'Région 3']);

        $locals = collect(range(1, 3))->map(fn ($i) => Organization::factory()->local($region1)->withResponsable([
            'name' => "Loc. {$i}",
            'email' => "local{$i}@example.com",
        ])->create([
            'name' => "Organisme local {$i}",
            'address' => "{$i} rue des Érables, Région 1",
        ]));

        $localInRegion2 = Organization::factory()->local($region2)->withResponsable([
            'name' => 'Loc. R2',
            'email' => 'local-r2-1@example.com',
        ])->create(['name' => 'Organisme local (Région 2) 1']);

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
