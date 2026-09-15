<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_cannot_reach_the_admin_dashboard(): void
    {
        $response = $this->get('/admin/tableau-de-bord');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_an_admin_can_log_in_with_a_password(): void
    {
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->post('/admin/connexion', [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_an_admin_sees_every_organization_regardless_of_hierarchy(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create(['name' => 'Provincial X']);
        $region = Organization::factory()->regional($provincial)->create(['name' => 'Région X']);
        Organization::factory()->local($region)->create(['name' => 'Local X']);

        $response = $this->actingAs($admin, 'admin')->get('/admin/tableau-de-bord');

        $response->assertOk();
        $response->assertSee('Provincial X');
        $response->assertSee('Région X');
        $response->assertSee('Local X');
    }

    public function test_an_admin_can_create_a_provincial_organization(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations', [
            'level' => 'provincial',
            'parent_id' => '',
            'name' => 'Nouveau provincial',
            'responsable_first_name' => 'A',
            'responsable_last_name' => 'B',
            'responsable_email' => 'nouveau-provincial@example.com',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertDatabaseHas('organizations', ['name' => 'Nouveau provincial', 'parent_id' => null]);
    }

    public function test_an_admin_cannot_create_a_regional_organization_without_a_matching_parent(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create();
        $otherRegional = Organization::factory()->regional($provincial)->create();

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations', [
            'level' => 'local',
            'parent_id' => $otherRegional->parent_id,
            'name' => 'Local invalide',
            'responsable_first_name' => 'A',
            'responsable_last_name' => 'B',
            'responsable_email' => 'invalide@example.com',
        ]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('organizations', ['name' => 'Local invalide']);
    }

    public function test_an_admin_can_import_organizations_from_a_csv(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'prov@example.com']);

        $csv = "level,parent_responsable_email,name,responsable_first_name,responsable_last_name,responsable_email\n"
            ."regional,prov@example.com,Région Importée,Jean,Tremblay,region-importee@example.com\n";

        $file = UploadedFile::fake()->createWithContent('organisations.csv', $csv);

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations/importer', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.organizations.import.create'));
        $this->assertDatabaseHas('organizations', [
            'name' => 'Région Importée',
            'parent_id' => $provincial->id,
        ]);
    }
}
