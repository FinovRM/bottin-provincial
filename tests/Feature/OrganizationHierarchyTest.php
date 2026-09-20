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
            'responsable_name' => 'Reg. 1',
            'responsable_email' => 'regional1@example.com',
            'responsable_email_confirmation' => 'regional1@example.com',
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
            'responsable_name' => 'Test Test',
            'responsable_email' => 'sous@example.com',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('organizations', ['name' => 'Sous-organisation']);
    }

    public function test_an_organization_can_update_its_own_coordinates(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($organization)->put("/organisations/{$organization->id}", [
            'name' => 'Nouveau nom',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'Nouveau nom',
        ]);
    }

    public function test_a_child_organization_cannot_rename_itself(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create(['name' => 'Région 1']);

        $response = $this->actingAs($regional)->put("/organisations/{$regional->id}", [
            'name' => 'Nouveau nom',
            'address' => '123 rue Test',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('organizations', [
            'id' => $regional->id,
            'name' => 'Région 1',
            'address' => '123 rue Test',
        ]);
    }

    public function test_an_organization_can_set_its_legal_name(): void
    {
        $organization = Organization::factory()->provincial()->create(['name' => 'AHM Acton Vale']);

        $response = $this->actingAs($organization)->put("/organisations/{$organization->id}", [
            'name' => 'AHM Acton Vale',
            'legal_name' => 'Association hockey mineur Acton Vale',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'legal_name' => 'Association hockey mineur Acton Vale',
        ]);
    }

    public function test_the_properties_page_shows_the_organizations_legal_name(): void
    {
        $organization = Organization::factory()->provincial()->create([
            'name' => 'AHM Acton Vale',
            'legal_name' => 'Association hockey mineur Acton Vale',
        ]);

        $response = $this->actingAs($organization)->get('/tableau-de-bord/proprietes');

        $response->assertOk();
        $response->assertSee('Organisation :');
        $response->assertSee('Nom légal :');
        $response->assertSee('Association hockey mineur Acton Vale');
    }

    public function test_the_organizations_directory_shows_the_legal_name(): void
    {
        $provincial = Organization::factory()->provincial()->create([
            'name' => 'AHM Acton Vale',
            'legal_name' => 'Association hockey mineur Acton Vale',
        ]);

        $response = $this->actingAs($provincial)->get('/tableau-de-bord/organisations');

        $response->assertOk();
        $response->assertSee('Nom légal');
        $response->assertSee('Association hockey mineur Acton Vale');
    }

    public function test_the_organizations_directory_shows_the_full_postal_address(): void
    {
        $provincial = Organization::factory()->provincial()->create([
            'name' => 'AHM Acton Vale',
            'address' => '1505 3e avenue',
            'city' => 'Acton Vale',
            'province' => 'Québec',
            'postal_code' => 'J0H1A0',
        ]);

        $response = $this->actingAs($provincial)->get('/tableau-de-bord/organisations');

        $response->assertOk();
        $response->assertSee('1505 3e avenue, Acton Vale, Québec J0H1A0');
    }

    public function test_an_organization_cannot_update_another_organizations_fiche(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();

        $response = $this->actingAs($regional)->put("/organisations/{$provincial->id}", [
            'name' => 'Hacked',
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
