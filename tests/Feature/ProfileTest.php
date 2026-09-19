<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_responsable_sees_their_own_coordinates_on_the_profile_page(): void
    {
        $organization = Organization::factory()->provincial()->create([
            'name' => 'Provincial Inc.',
            'responsable_name' => 'Jeanne Tremblay',
            'responsable_email' => 'jeanne@example.com',
            'responsable_cell_phone' => '5145551234',
        ]);

        $response = $this->actingAs($organization)->get('/profil');

        $response->assertOk();
        $response->assertSee('Mes coordonnées');
        $response->assertSee('Jeanne Tremblay');
        $response->assertSee('Responsable de bottin');
        $response->assertSee('Provincial Inc.');
        $response->assertSee('jeanne@example.com');
        $response->assertSee('(514) 555-1234');
        $response->assertSee('Mes filtres personnels');
        $response->assertSee('Régional');
        $response->assertSee('Local');
        $response->assertSee('Rôle');
        $response->assertSee('Ajouter un filtre');
        $response->assertSee('Réinitialiser');
    }

    public function test_a_member_sees_their_own_coordinates_on_the_profile_page(): void
    {
        $organization = Organization::factory()->provincial()->create(['name' => 'Provincial Inc.']);
        $member = Member::create(['name' => 'Bénévole Test', 'email' => 'benevole@example.com']);
        $organization->memberRoles()->create(['member_id' => $member->id, 'role' => 'Trésorier']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'benevole@example.com']);
        $this->post($url, ['confirmed' => '1']);

        $response = $this->get('/profil');

        $response->assertOk();
        $response->assertSee('Bénévole Test');
        $response->assertSee('Trésorier');
        $response->assertSee('Provincial Inc.');
        $response->assertSee('benevole@example.com');
    }
}
