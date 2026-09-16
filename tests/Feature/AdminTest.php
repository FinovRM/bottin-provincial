<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
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

        $response = $this->actingAs($admin, 'admin')->get('/admin/organisations');

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

        $response->assertRedirect(route('admin.organizations.index'));
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

    public function test_an_admin_can_delete_a_childless_organization(): void
    {
        $admin = Admin::factory()->create();
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($admin, 'admin')->delete("/admin/organisations/{$organization->id}");

        $response->assertRedirect(route('admin.organizations.index'));
        $this->assertDatabaseMissing('organizations', ['id' => $organization->id]);
    }

    public function test_an_admin_cannot_delete_an_organization_that_still_has_children(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create();
        Organization::factory()->regional($provincial)->create();

        $response = $this->actingAs($admin, 'admin')->delete("/admin/organisations/{$provincial->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('organizations', ['id' => $provincial->id]);
    }

    public function test_an_admin_sees_every_member_and_can_add_a_role(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create();
        $existing = Member::create(['name' => 'Membre existant', 'email' => 'existant@example.com']);
        $provincial->memberRoles()->create(['member_id' => $existing->id, 'role' => 'Direction']);

        $response = $this->actingAs($admin, 'admin')->get('/admin/membres');
        $response->assertOk();
        $response->assertSee('Membre existant');

        $response = $this->actingAs($admin, 'admin')->post('/admin/membres', [
            'organization_id' => $provincial->id,
            'role' => 'Trésorier',
            'name' => 'Nouveau membre',
            'email' => 'nouveau-membre@example.com',
        ]);

        $response->assertRedirect(route('admin.members.index'));
        $this->assertDatabaseHas('members', ['email' => 'nouveau-membre@example.com']);
        $this->assertDatabaseHas('member_roles', ['organization_id' => $provincial->id, 'role' => 'Trésorier']);
    }

    public function test_an_admin_can_delete_a_role(): void
    {
        $admin = Admin::factory()->create();
        $organization = Organization::factory()->provincial()->create();
        $member = Member::create(['name' => 'À retirer', 'email' => 'retirer@example.com']);
        $memberRole = $organization->memberRoles()->create(['member_id' => $member->id, 'role' => 'Bénévole']);

        $response = $this->actingAs($admin, 'admin')->delete("/admin/membres/{$memberRole->id}");

        $response->assertRedirect(route('admin.members.index'));
        $this->assertDatabaseMissing('member_roles', ['id' => $memberRole->id]);
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    public function test_an_admin_can_import_members_from_a_csv(): void
    {
        $admin = Admin::factory()->create();
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'org@example.com']);

        $csv = "organization_responsable_email,role,name,email,cell_phone\n"
            ."org@example.com,Bénévole,Membre Importé,membre-importe@example.com,514-555-9999\n";

        $file = UploadedFile::fake()->createWithContent('membres.csv', $csv);

        $response = $this->actingAs($admin, 'admin')->post('/admin/membres/importer', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.members.import.create'));
        $this->assertDatabaseHas('members', ['email' => 'membre-importe@example.com']);
        $this->assertDatabaseHas('member_roles', ['organization_id' => $organization->id, 'role' => 'Bénévole']);
    }
}
