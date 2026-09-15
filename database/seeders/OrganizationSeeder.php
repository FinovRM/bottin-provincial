<?php

namespace Database\Seeders;

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
        ]);

        $region1 = Organization::factory()->regional($provincial)->create([
            'name' => 'Région 1',
            'responsable_first_name' => 'Reg.',
            'responsable_last_name' => '1',
            'responsable_email' => 'regional1@example.com',
        ]);

        Organization::factory()->regional($provincial)->create([
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

        foreach (range(1, 3) as $i) {
            Organization::factory()->local($region1)->create([
                'name' => "Organisme local {$i}",
                'responsable_first_name' => 'Loc.',
                'responsable_last_name' => (string) $i,
                'responsable_email' => "local{$i}@example.com",
            ]);
        }
    }
}
