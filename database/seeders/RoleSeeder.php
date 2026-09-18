<?php

namespace Database\Seeders;

use App\Enums\OrganizationLevel;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            OrganizationLevel::Provincial->value => [
                'Président',
                'Directeur général',
                'Comptabilité',
                'Mobilisation des membres',
            ],
            OrganizationLevel::Regional->value => [
                'Président',
                'Directeur général',
                'Mobilisation des membres',
                'Registraire régional',
                'Directeur des tournois',
                'Gestion des plaintes',
            ],
            OrganizationLevel::Local->value => [
                'Président',
                'Directeur général',
                'Comptabilité',
                'Régisseur de glace',
                'Registraire',
            ],
        ];

        foreach ($roles as $level => $names) {
            foreach ($names as $name) {
                Role::firstOrCreate(['level' => $level, 'name' => $name]);
            }
        }
    }
}
