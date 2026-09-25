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

    public function test_an_admin_can_filter_organizations_by_level_parent_and_organization(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create(['name' => 'Provincial X']);
        $regionA = Organization::factory()->regional($provincial)->create(['name' => 'Région A']);
        $regionB = Organization::factory()->regional($provincial)->create(['name' => 'Région B']);
        Organization::factory()->local($regionA)->create(['name' => 'Local A']);
        Organization::factory()->local($regionB)->create(['name' => 'Local B']);

        $admin = $this->actingAs($admin, 'admin');

        $admin->get('/admin/organisations?level=regional')
            ->assertSeeInOrder(['Région A', 'Région B'])
            ->assertDontSee('font-medium">Local A</td>', false)
            ->assertDontSee('font-medium">Provincial X</td>', false);

        $admin->get("/admin/organisations?parent_id={$regionA->id}")
            ->assertSee('font-medium">Local A</td>', false)
            ->assertDontSee('font-medium">Local B</td>', false);

        $admin->get("/admin/organisations?organization_id={$regionB->id}")
            ->assertSee('font-medium">Région B</td>', false)
            ->assertDontSee('font-medium">Région A</td>', false);
    }

    public function test_an_admin_can_create_a_provincial_organization(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations', [
            'level' => 'provincial',
            'parent_id' => '',
            'name' => 'Nouveau provincial',
            'responsable_name' => 'A B',
            'responsable_email' => 'nouveau-provincial@example.com',
            'responsable_email_confirmation' => 'nouveau-provincial@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertRedirect(route('admin.organizations.index'));
        $this->assertDatabaseHas('organizations', [
            'name' => 'Nouveau provincial',
            'parent_id' => null,
            'responsable_cell_phone' => '5145551234',
        ]);
    }

    public function test_creating_an_organization_requires_matching_email_confirmation(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations', [
            'level' => 'provincial',
            'parent_id' => '',
            'name' => 'Nouveau provincial',
            'responsable_name' => 'A B',
            'responsable_email' => 'nouveau-provincial@example.com',
            'responsable_email_confirmation' => 'different@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertSessionHasErrors('responsable_email');
        $this->assertDatabaseMissing('organizations', ['name' => 'Nouveau provincial']);
    }

    public function test_creating_an_organization_requires_the_cell_phone(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations', [
            'level' => 'provincial',
            'parent_id' => '',
            'name' => 'Nouveau provincial',
            'responsable_name' => 'A B',
            'responsable_email' => 'nouveau-provincial@example.com',
            'responsable_email_confirmation' => 'nouveau-provincial@example.com',
        ]);

        $response->assertSessionHasErrors('responsable_cell_phone');
        $this->assertDatabaseMissing('organizations', ['name' => 'Nouveau provincial']);
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
            'responsable_name' => 'A B',
            'responsable_email' => 'invalide@example.com',
            'responsable_email_confirmation' => 'invalide@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('organizations', ['name' => 'Local invalide']);
    }

    public function test_an_admin_can_update_an_organizations_parent(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create();
        $otherProvincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();

        $response = $this->actingAs($admin, 'admin')->put("/admin/organisations/{$regional->id}", [
            'level' => 'regional',
            'parent_id' => $otherProvincial->id,
            'name' => $regional->name,
            'responsable_name' => $regional->responsable_name,
            'responsable_email' => $regional->responsable_email,
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertRedirect(route('admin.organizations.index'));
        $this->assertDatabaseHas('organizations', ['id' => $regional->id, 'parent_id' => $otherProvincial->id]);
    }

    public function test_an_admin_cannot_assign_a_mismatched_parent(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $otherRegional = Organization::factory()->regional($provincial)->create();

        $response = $this->actingAs($admin, 'admin')->put("/admin/organisations/{$regional->id}", [
            'level' => 'regional',
            'parent_id' => $otherRegional->id,
            'name' => $regional->name,
            'responsable_name' => $regional->responsable_name,
            'responsable_email' => $regional->responsable_email,
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_updating_an_organization_requires_the_cell_phone(): void
    {
        $admin = Admin::factory()->create();
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($admin, 'admin')->put("/admin/organisations/{$organization->id}", [
            'level' => 'provincial',
            'name' => $organization->name,
            'responsable_name' => $organization->responsable_name,
            'responsable_email' => $organization->responsable_email,
        ]);

        $response->assertSessionHasErrors('responsable_cell_phone');
    }

    public function test_an_admin_cannot_change_the_level_of_an_organization_with_children(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create();
        Organization::factory()->regional($provincial)->create();

        $response = $this->actingAs($admin, 'admin')->put("/admin/organisations/{$provincial->id}", [
            'level' => 'regional',
            'parent_id' => Organization::factory()->provincial()->create()->id,
            'name' => $provincial->name,
            'responsable_name' => $provincial->responsable_name,
            'responsable_email' => $provincial->responsable_email,
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertSessionHasErrors('level');
        $this->assertDatabaseHas('organizations', ['id' => $provincial->id, 'level' => 'provincial']);
    }

    public function test_an_admin_can_import_organizations_from_a_csv(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'prov@example.com']);

        $csv = "level,parent_responsable_email,name,responsable_name,responsable_email,responsable_cell_phone\n"
            ."regional,prov@example.com,Région Importée,Jean Tremblay,region-importee@example.com,514-555-1234\n";

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

    public function test_the_organization_import_accepts_level_labels(): void
    {
        $admin = Admin::factory()->create();

        $csv = "level,parent_responsable_email,name,responsable_name,responsable_email,responsable_cell_phone\n"
            ."Provincial,,Provincial Importé,Jean Tremblay,prov-importe@example.com,514-555-1234\n"
            ."Régional,prov-importe@example.com,Région Importée,Marie Roy,region-importee@example.com,514-555-2345\n"
            ."LOCAL,region-importee@example.com,Local Importé,Luc Gagnon,local-importe@example.com,514-555-3456\n";

        $file = UploadedFile::fake()->createWithContent('organisations.csv', $csv);

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations/importer', [
            'file' => $file,
        ]);

        $response->assertSessionHas('import_errors', []);
        $this->assertDatabaseHas('organizations', ['name' => 'Provincial Importé', 'level' => 'provincial']);
        $this->assertDatabaseHas('organizations', ['name' => 'Région Importée', 'level' => 'regional']);
        $this->assertDatabaseHas('organizations', ['name' => 'Local Importé', 'level' => 'local']);
    }

    public function test_the_organization_import_rejects_a_row_without_a_valid_responsable_email(): void
    {
        $admin = Admin::factory()->create();
        Organization::factory()->provincial()->create(['responsable_email' => 'prov@example.com']);

        $csv = "level,parent_responsable_email,name,responsable_name,responsable_email,responsable_cell_phone\n"
            ."regional,prov@example.com,Région Décalée,jean@example.com,,514-555-1234\n";

        $file = UploadedFile::fake()->createWithContent('organisations.csv', $csv);

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations/importer', [
            'file' => $file,
        ]);

        $response->assertSessionHas('import_errors', fn ($errors) => count($errors) === 1);
        $this->assertDatabaseMissing('organizations', ['name' => 'Région Décalée']);
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

        $this->travelTo(now()->addMinute());

        $response = $this->actingAs($admin, 'admin')->post('/admin/membres', [
            'organization_id' => $provincial->id,
            'role' => 'Trésorier',
            'name' => 'Nouveau membre',
            'email' => 'nouveau-membre@example.com',
        ]);

        $response->assertRedirect(route('admin.members.index'));
        $this->assertDatabaseHas('members', ['email' => 'nouveau-membre@example.com']);
        $this->assertDatabaseHas('member_roles', ['organization_id' => $provincial->id, 'role' => 'Trésorier']);
        $this->assertTrue($provincial->fresh()->updated_at->gt($provincial->updated_at));
    }

    public function test_an_admin_can_delete_a_role(): void
    {
        $admin = Admin::factory()->create();
        $organization = Organization::factory()->provincial()->create();
        $member = Member::create(['name' => 'À retirer', 'email' => 'retirer@example.com']);
        $memberRole = $organization->memberRoles()->create(['member_id' => $member->id, 'role' => 'Bénévole']);
        $updatedAt = $organization->fresh()->updated_at;

        $this->travelTo(now()->addMinute());

        $response = $this->actingAs($admin, 'admin')->delete("/admin/membres/{$memberRole->id}");

        $response->assertRedirect(route('admin.members.index'));
        $this->assertDatabaseMissing('member_roles', ['id' => $memberRole->id]);
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertTrue($organization->fresh()->updated_at->gt($updatedAt));
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
