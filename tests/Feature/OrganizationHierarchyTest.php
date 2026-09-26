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
            'group' => 'organisation',
            'responsable_name' => 'Reg. 1',
            'responsable_email' => 'regional1@example.com',
            'responsable_email_confirmation' => 'regional1@example.com',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('organizations', [
            'name' => 'Région 1',
            'parent_id' => $provincial->id,
            'level' => OrganizationLevel::Regional->value,
            'group' => 'organisation',
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

        $response = $this->actingAs($organization)->get('/proprietes');

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

        $response = $this->actingAs($this->viewerOf($provincial), 'member')->get('/bottin/organisations');

        $response->assertOk();
        $response->assertSee('Association hockey mineur Acton Vale');
    }

    public function test_the_organizations_directory_offers_to_copy_the_responsables_emails(): void
    {
        $provincial = Organization::factory()->provincial()->withResponsable(['email' => 'prov@example.com'])->create();
        Organization::factory()->regional($provincial)->withResponsable(['email' => 'region@example.com'])->create();

        $response = $this->actingAs($this->viewerOf($provincial), 'member')->get('/bottin/organisations');

        $response->assertSee('Copier les courriels dans le presse-papier');
        $response->assertSee('prov@example.com', false);
        $response->assertSee('region@example.com', false);
    }

    public function test_the_organizations_directory_can_filter_on_several_local_organizations(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $first = Organization::factory()->local($regional)->create(['name' => 'Local choisi 1']);
        $second = Organization::factory()->local($regional)->create(['name' => 'Local choisi 2']);
        Organization::factory()->local($regional)->create(['name' => 'Local écarté']);

        $query = http_build_query(['local_ids' => [$first->id, $second->id]]);
        $response = $this->actingAs($this->viewerOf($provincial), 'member')->get("/bottin/organisations?{$query}");

        $response->assertOk();
        $response->assertSee('Local choisi 1');
        $response->assertSee('Local choisi 2');
        $response->assertDontSee('mailto:'.Organization::where('name', 'Local écarté')->first()->responsables->first()->email, false);

        $csv = $this->actingAs($this->viewerOf($provincial), 'member')->get("/bottin/organisations/exporter?{$query}")->streamedContent();

        $this->assertStringContainsString('Local choisi 1', $csv);
        $this->assertStringContainsString('Local choisi 2', $csv);
        $this->assertStringNotContainsString('Local écarté', $csv);
    }

    public function test_the_organizations_directory_can_isolate_my_parent_and_my_organization(): void
    {
        $provincial = Organization::factory()->provincial()->withResponsable(['email' => 'prov@example.com'])->create(['name' => 'Bureau provincial']);
        $regional = Organization::factory()->regional($provincial)->withResponsable(['email' => 'moi@example.com'])->create(['name' => 'Ma région']);
        Organization::factory()->regional($provincial)->withResponsable(['email' => 'autre@example.com'])->create(['name' => 'Autre région']);
        $viewer = $this->viewerOf($regional);

        $page = $this->actingAs($viewer, 'member')->get('/bottin/organisations');
        $page->assertSee('Mon parent');
        $page->assertSee('Mon organisation');
        $page->assertSee('mailto:autre@example.com', false);
        $page->assertDontSee('name="provincial_id"', false);

        $mine = $this->actingAs($viewer, 'member')->get('/bottin/organisations?my_organization=1');
        $mine->assertSee('mailto:moi@example.com', false);
        $mine->assertDontSee('mailto:autre@example.com', false);
        $mine->assertDontSee('mailto:prov@example.com', false);

        $parent = $this->actingAs($viewer, 'member')->get('/bottin/organisations?my_direction=1');
        $parent->assertSee('mailto:prov@example.com', false);
        $parent->assertDontSee('mailto:moi@example.com', false);
        $parent->assertDontSee('mailto:autre@example.com', false);

        $csv = $this->actingAs($viewer, 'member')->get('/bottin/organisations/exporter?my_direction=1&my_organization=1')->streamedContent();
        $this->assertStringContainsString('Bureau provincial', $csv);
        $this->assertStringContainsString('Ma région', $csv);
        $this->assertStringNotContainsString('Autre région', $csv);
    }

    public function test_the_organizations_directory_can_be_exported_to_csv_with_its_filters(): void
    {
        $provincial = Organization::factory()->provincial()->create(['name' => 'Bureau provincial']);
        $regional = Organization::factory()->regional($provincial)->create([
            'name' => 'Région 1',
            'legal_name' => 'Association régionale 1',
        ]);
        $otherRegional = Organization::factory()->regional($provincial)->create(['name' => 'Région 2']);
        Organization::factory()->local($regional)->create(['name' => 'Local de la région 1']);
        Organization::factory()->local($otherRegional)->create(['name' => 'Local de la région 2']);

        $response = $this->actingAs($this->viewerOf($provincial), 'member')->get('/bottin/organisations');
        $response->assertSee(route('bottin.organizations.export'), false);

        $response = $this->actingAs($this->viewerOf($provincial), 'member')->get('/bottin/organisations/exporter');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Bureau provincial', $content);
        $this->assertStringContainsString('Association régionale 1', $content);

        $filtered = $this->actingAs($this->viewerOf($provincial), 'member')->get("/bottin/organisations/exporter?regional_id={$regional->id}")->streamedContent();
        $this->assertStringContainsString('Local de la région 1', $filtered);
        $this->assertStringNotContainsString('Local de la région 2', $filtered);
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

        $response = $this->actingAs($this->viewerOf($provincial), 'member')->get('/bottin/organisations');

        $response->assertOk();
        $response->assertSeeInOrder(['<span class="block">1505 3e avenue</span>', '<span class="block">Acton Vale, Québec J0H1A0</span>'], false);
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
