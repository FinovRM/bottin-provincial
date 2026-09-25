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

    public function test_a_responsable_cannot_reach_the_profile_page(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $this->actingAs($organization)->get('/profil')->assertRedirect(route('bottin'));
        $this->actingAs($organization)->post('/profil/filtres', ['name' => 'Filtre'])->assertRedirect(route('bottin'));
        $this->assertDatabaseCount('personal_filters', 0);
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

    public function test_the_profile_page_offers_to_add_or_show_and_modify_personal_filters(): void
    {
        $organization = Organization::factory()->provincial()->create();
        $member = $this->viewerOf($organization);
        $member->personalFilters()->create(['name' => 'Mes trésoriers', 'roles' => ['Trésorier']]);

        $response = $this->actingAs($member, 'member')->get('/profil');

        $response->assertOk();
        $response->assertSee('Mes trésoriers');
        $response->assertSee('Montrer / Modifier');
        $response->assertSee('+ Ajouter un filtre');
        // The choice area and its buttons, hidden until a filter is added or shown.
        $response->assertSee('id="filter-editor" hidden', false);
        $response->assertSee('Accepter le filtre');
        $response->assertSee('Réinitialiser');
        $response->assertSee('Quitter');
        $response->assertSee(route('profile.filters.update', $member->personalFilters()->first()), false);
    }
}
