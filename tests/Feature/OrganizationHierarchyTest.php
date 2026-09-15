<?php

namespace Tests\Feature;

use App\Enums\OrganizationLevel;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_provincial_organization_can_create_a_regional_child(): void
    {
        $provincial = Organization::factory()->provincial()->create();

        $response = $this->actingAs($provincial)->post('/organisations', [
            'name' => 'Région 1',
            'responsable_first_name' => 'Reg.',
            'responsable_last_name' => '1',
            'responsable_email' => 'regional1@example.com',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('organizations', [
            'name' => 'Région 1',
            'parent_id' => $provincial->id,
            'level' => OrganizationLevel::Regional->value,
        ]);
    }

    public function test_a_local_organization_cannot_create_children(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $local = Organization::factory()->local($regional)->create();

        $response = $this->actingAs($local)->post('/organisations', [
            'name' => 'Sous-organisation',
            'responsable_first_name' => 'Test',
            'responsable_last_name' => 'Test',
            'responsable_email' => 'sous@example.com',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('organizations', ['name' => 'Sous-organisation']);
    }

    public function test_an_organization_can_update_its_own_fiche_and_transfer_access_by_email(): void
    {
        $organization = Organization::factory()->provincial()->create([
            'responsable_email' => 'ancien@example.com',
        ]);

        $response = $this->actingAs($organization)->put("/organisations/{$organization->id}", [
            'name' => $organization->name,
            'responsable_first_name' => 'Nouveau',
            'responsable_last_name' => 'Responsable',
            'responsable_email' => 'nouveau@example.com',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'responsable_email' => 'nouveau@example.com',
        ]);
    }

    public function test_an_organization_cannot_update_another_organizations_fiche(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();

        $response = $this->actingAs($regional)->put("/organisations/{$provincial->id}", [
            'name' => 'Hacked',
            'responsable_first_name' => $provincial->responsable_first_name,
            'responsable_last_name' => $provincial->responsable_last_name,
            'responsable_email' => $provincial->responsable_email,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('organizations', ['name' => 'Hacked']);
    }

    public function test_the_public_bottin_lists_the_organization_tree(): void
    {
        $provincial = Organization::factory()->provincial()->create(['name' => 'Bureau provincial']);
        Organization::factory()->regional($provincial)->create(['name' => 'Région 1']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Bureau provincial');
        $response->assertSee('Région 1');
    }
}
