<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinimumRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_organization_can_add_a_minimum_role_for_its_children(): void
    {
        $provincial = Organization::factory()->provincial()->create();

        $response = $this->actingAs($provincial)->post('/profil/roles-minimum', [
            'name' => 'Président',
        ]);

        $response->assertRedirect(route('profile'));
        $this->assertDatabaseHas('minimum_roles', [
            'organization_id' => $provincial->id,
            'name' => 'Président',
        ]);
    }

    public function test_a_local_organization_cannot_add_a_minimum_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $local = Organization::factory()->local($regional)->create();

        $response = $this->actingAs($local)->post('/profil/roles-minimum', [
            'name' => 'Président',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('minimum_roles', ['name' => 'Président']);
    }

    public function test_a_parent_organization_cannot_add_a_duplicate_minimum_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $provincial->minimumRoles()->create(['name' => 'Président']);

        $response = $this->actingAs($provincial)->post('/profil/roles-minimum', [
            'name' => 'Président',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('minimum_roles', 1);
    }

    public function test_a_parent_organization_can_delete_its_own_minimum_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $minimumRole = $provincial->minimumRoles()->create(['name' => 'Président']);

        $response = $this->actingAs($provincial)->delete("/profil/roles-minimum/{$minimumRole->id}");

        $response->assertRedirect(route('profile'));
        $this->assertDatabaseMissing('minimum_roles', ['id' => $minimumRole->id]);
    }

    public function test_an_organization_cannot_delete_another_organizations_minimum_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $otherProvincial = Organization::factory()->provincial()->create();
        $minimumRole = $otherProvincial->minimumRoles()->create(['name' => 'Président']);

        $response = $this->actingAs($provincial)->delete("/profil/roles-minimum/{$minimumRole->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('minimum_roles', ['id' => $minimumRole->id]);
    }

    public function test_the_profile_page_shows_minimum_roles_only_for_a_parent_organization(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $provincial->minimumRoles()->create(['name' => 'Président']);

        $response = $this->actingAs($provincial)->get('/profil');

        $response->assertOk();
        $response->assertSee('Rôles minimum de mes organisations enfant');
        $response->assertSee('Président');
    }

    public function test_the_profile_page_hides_minimum_roles_for_a_local_organization(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $local = Organization::factory()->local($regional)->create();

        $response = $this->actingAs($local)->get('/profil');

        $response->assertOk();
        $response->assertDontSee('Rôles minimum de mes organisations enfant');
    }
}
