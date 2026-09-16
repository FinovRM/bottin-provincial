<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_single_organization_responsable_is_redirected_away_from_the_switch_page(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($organization)->get('/tableau-de-bord/organisations-gerees');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_a_multi_organization_responsable_sees_every_organization_they_manage(): void
    {
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'multi@example.com', 'name' => 'Provincial X']);
        $regional = Organization::factory()->regional($provincial)->create(['responsable_email' => 'multi@example.com', 'name' => 'Régional X']);

        $response = $this->actingAs($provincial)->get('/tableau-de-bord/organisations-gerees');

        $response->assertOk();
        $response->assertSee('Provincial X');
        $response->assertSee('Régional X');
    }

    public function test_switching_to_an_organization_managed_by_the_same_responsable_logs_in_as_it(): void
    {
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'multi@example.com']);
        $regional = Organization::factory()->regional($provincial)->create(['responsable_email' => 'multi@example.com']);

        $response = $this->actingAs($provincial)->post("/tableau-de-bord/organisations-gerees/{$regional->id}");

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($regional);
    }

    public function test_switching_to_an_organization_managed_by_someone_else_is_forbidden(): void
    {
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'multi@example.com']);
        $unrelated = Organization::factory()->provincial()->create(['responsable_email' => 'autre@example.com']);

        $response = $this->actingAs($provincial)->post("/tableau-de-bord/organisations-gerees/{$unrelated->id}");

        $response->assertForbidden();
        $this->assertAuthenticatedAs($provincial);
    }
}
