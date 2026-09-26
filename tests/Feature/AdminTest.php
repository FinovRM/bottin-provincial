<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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
        $organization = Organization::where('name', 'Nouveau provincial')->whereNull('parent_id')->firstOrFail();
        $this->assertDatabaseHas('responsables', [
            'organization_id' => $organization->id,
            'name' => 'A B',
            'email' => 'nouveau-provincial@example.com',
            'cell_phone' => '5145551234',
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
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_an_admin_can_add_and_remove_responsables_but_keeps_at_least_one(): void
    {
        $admin = Admin::factory()->create();
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'premier@example.com']);
        $first = $organization->responsables()->first();

        $this->actingAs($admin, 'admin')->post("/admin/organisations/{$organization->id}/responsables", [
            'responsable_name' => 'Deuxième',
            'responsable_email' => 'deuxieme@example.com',
            'responsable_cell_phone' => '514-555-0000',
        ])->assertRedirect(route('admin.organizations.edit', $organization));

        $second = $organization->responsables()->where('email', 'deuxieme@example.com')->firstOrFail();

        $this->actingAs($admin, 'admin')->delete("/admin/responsables/{$first->id}");
        $this->assertModelMissing($first);

        $this->actingAs($admin, 'admin')->delete("/admin/responsables/{$second->id}");
        $this->assertModelExists($second);
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

    public function test_the_organization_import_allows_one_responsable_for_several_organizations(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'prov@example.com']);

        $csv = "level,parent_responsable_email,name,responsable_name,responsable_email,responsable_cell_phone\n"
            ."regional,prov@example.com,Région A,Marc D,marc@example.com,514-555-1234\n"
            ."regional,prov@example.com,Région B,Marc D,marc@example.com,514-555-1234\n";

        $file = UploadedFile::fake()->createWithContent('organisations.csv', $csv);

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations/importer', ['file' => $file]);

        $response->assertSessionHas('import_errors', []);
        $this->assertSame(2, Organization::whereHas('responsables', fn ($responsables) => $responsables->where('email', 'marc@example.com'))
            ->where('parent_id', $provincial->id)->count());
    }

    public function test_the_organization_import_skips_an_organization_that_already_exists(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'prov@example.com']);
        Organization::factory()->regional($provincial)->create(['name' => 'Région A']);

        $csv = "level,parent_responsable_email,name,responsable_name,responsable_email,responsable_cell_phone\n"
            ."regional,prov@example.com,Région A,Marc D,marc@example.com,514-555-1234\n";

        $file = UploadedFile::fake()->createWithContent('organisations.csv', $csv);

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations/importer', ['file' => $file]);

        $response->assertSessionHas('import_errors', fn ($errors) => count($errors) === 1 && str_contains($errors[0], 'existe déjà'));
        $this->assertSame(1, Organization::where('name', 'Région A')->count());
    }

    public function test_the_organization_import_finds_the_parent_at_the_expected_level(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'marc@example.com']);
        $regional = Organization::factory()->regional($provincial)->create(['responsable_email' => 'marc@example.com']);
        $otherProvincial = Organization::factory()->provincial()->create(['responsable_email' => 'marc@example.com']);

        $csv = "level,parent_responsable_email,name,responsable_name,responsable_email,responsable_cell_phone\n"
            ."local,marc@example.com,Local A,Jean T,jean@example.com,514-555-1234\n"
            ."regional,marc@example.com,Région Ambiguë,Jean T,jean@example.com,514-555-1234\n";

        $file = UploadedFile::fake()->createWithContent('organisations.csv', $csv);

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations/importer', ['file' => $file]);

        $this->assertDatabaseHas('organizations', ['name' => 'Local A', 'parent_id' => $regional->id]);
        $response->assertSessionHas('import_errors', fn ($errors) => count($errors) === 1 && str_contains($errors[0], 'plusieurs organisations parentes'));
        $this->assertDatabaseMissing('organizations', ['name' => 'Région Ambiguë']);
    }

    public function test_an_admin_can_create_an_organization_for_a_responsable_already_in_charge_of_another(): void
    {
        $admin = Admin::factory()->create();
        Organization::factory()->provincial()->create(['responsable_email' => 'marc@example.com']);

        $response = $this->actingAs($admin, 'admin')->post('/admin/organisations', [
            'level' => 'provincial',
            'parent_id' => '',
            'name' => 'Deuxième provincial',
            'responsable_name' => 'Marc D',
            'responsable_email' => 'marc@example.com',
            'responsable_email_confirmation' => 'marc@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertRedirect(route('admin.organizations.index'));
        $this->assertSame(2, Organization::whereHas('responsables', fn ($responsables) => $responsables->where('email', 'marc@example.com'))->count());
    }

    public function test_updating_a_responsables_coordinates_updates_all_their_organizations(): void
    {
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'marc@example.com', 'responsable_name' => 'Prénom Nom']);
        $regional = Organization::factory()->regional($provincial)->create(['responsable_email' => 'marc@example.com']);
        $other = Organization::factory()->regional($provincial)->create(['responsable_email' => 'autre@example.com', 'responsable_name' => 'Autre']);

        $provincial->responsables()->first()->update([
            'name' => 'Marc Desilets',
            'email' => 'marc.d@example.com',
            'cell_phone' => '514-555-1234',
        ]);

        $this->assertDatabaseHas('responsables', [
            'organization_id' => $regional->id,
            'name' => 'Marc Desilets',
            'email' => 'marc.d@example.com',
            'cell_phone' => '5145551234',
        ]);
        $this->assertDatabaseHas('responsables', ['organization_id' => $other->id, 'name' => 'Autre']);
    }

    public function test_a_responsable_added_elsewhere_keeps_the_coordinates_on_file(): void
    {
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'marc@example.com', 'responsable_name' => 'Marc']);
        $regional = Organization::factory()->regional($provincial)->create();

        $regional->responsables()->create(['name' => 'Autre orthographe', 'email' => 'marc@example.com']);

        $this->assertDatabaseHas('responsables', ['organization_id' => $regional->id, 'email' => 'marc@example.com', 'name' => 'Marc']);
    }

    public function test_the_organization_import_keeps_the_coordinates_of_a_known_responsable(): void
    {
        $admin = Admin::factory()->create();
        Organization::factory()->provincial()->create([
            'responsable_email' => 'prov@example.com',
            'responsable_name' => 'Alain Dufour',
            'responsable_cell_phone' => '418-555-0000',
        ]);

        $csv = "level,parent_responsable_email,name,responsable_name,responsable_email,responsable_cell_phone\n"
            ."regional,prov@example.com,Région A,Prenom Nom,prov@example.com,\n";

        $file = UploadedFile::fake()->createWithContent('organisations.csv', $csv);

        $this->actingAs($admin, 'admin')->post('/admin/organisations/importer', ['file' => $file]);

        $this->assertDatabaseHas('responsables', [
            'organization_id' => Organization::where('name', 'Région A')->value('id'),
            'name' => 'Alain Dufour',
            'cell_phone' => '4185550000',
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

    public function test_an_admin_can_filter_members_by_organization_level(): void
    {
        $admin = Admin::factory()->create();
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $provincialMember = Member::create(['name' => 'Membre Provincial', 'email' => 'membre-provincial@example.com']);
        $regionalMember = Member::create(['name' => 'Membre Régional', 'email' => 'membre-regional@example.com']);
        $provincial->memberRoles()->create(['member_id' => $provincialMember->id, 'role' => 'Direction']);
        $regional->memberRoles()->create(['member_id' => $regionalMember->id, 'role' => 'Direction']);

        $response = $this->actingAs($admin, 'admin')->get('/admin/membres?level=regional');

        $response->assertOk();
        $response->assertSee('Membre Régional');
        $response->assertDontSee('Membre Provincial');
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

    public function test_a_member_import_reads_the_extension_column_or_splits_it_from_the_phone(): void
    {
        $admin = Admin::factory()->create();
        Organization::factory()->provincial()->create(['responsable_email' => 'org@example.com']);

        $csv = "organization_responsable_email,role,name,email,cell_phone,extension\n"
            ."org@example.com,Bénévole,Avec Poste,avec-poste@example.com,819-562-0044,221\n"
            ."org@example.com,Bénévole,Poste Collé,poste-colle@example.com,8195620044234\n";

        $this->actingAs($admin, 'admin')->post('/admin/membres/importer', [
            'file' => UploadedFile::fake()->createWithContent('membres.csv', $csv),
        ]);

        $this->assertDatabaseHas('members', ['email' => 'avec-poste@example.com', 'cell_phone' => '8195620044', 'extension' => '221']);
        $this->assertDatabaseHas('members', ['email' => 'poste-colle@example.com', 'cell_phone' => '8195620044', 'extension' => '234']);
    }

    public function test_the_admin_menu_leads_to_the_properties_page(): void
    {
        $admin = Admin::factory()->create(['name' => 'Anne Admin']);

        $this->actingAs($admin, 'admin')->get('/admin/tableau-de-bord')
            ->assertSee(route('admin.properties'), false);

        $this->actingAs($admin, 'admin')->get('/admin/proprietes')
            ->assertOk()
            ->assertSee("Nom de l'administrateur", false)
            ->assertSee('Anne Admin');
    }

    public function test_an_admin_can_change_their_name(): void
    {
        $admin = Admin::factory()->create(['name' => 'Ancien nom']);

        $this->actingAs($admin, 'admin')->put('/admin/proprietes/nom', ['name' => 'Nouveau nom'])
            ->assertRedirect(route('admin.properties'));

        $this->assertSame('Nouveau nom', $admin->fresh()->name);
    }

    public function test_an_admin_can_change_their_password_with_the_current_one(): void
    {
        $admin = Admin::factory()->create(['password' => 'ancien-secret']);

        $this->actingAs($admin, 'admin')->put('/admin/proprietes/mot-de-passe', [
            'current_password' => 'mauvais',
            'password' => 'nouveau-secret',
            'password_confirmation' => 'nouveau-secret',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($admin, 'admin')->put('/admin/proprietes/mot-de-passe', [
            'current_password' => 'ancien-secret',
            'password' => 'nouveau-secret',
            'password_confirmation' => 'nouveau-secret',
        ])->assertRedirect(route('admin.properties'));

        $this->assertTrue(Hash::check('nouveau-secret', $admin->fresh()->password));
    }

    public function test_an_admin_can_add_another_admin(): void
    {
        $admin = Admin::factory()->create(['email' => 'moi@example.com']);

        $this->actingAs($admin, 'admin')->post('/admin/proprietes/administrateurs', [
            'new_admin_name' => 'Bruno Admin',
            'new_admin_email' => 'bruno@example.com',
            'new_admin_password' => 'secret-bruno',
            'new_admin_password_confirmation' => 'secret-bruno',
        ])->assertRedirect(route('admin.properties'));

        $added = Admin::where('email', 'bruno@example.com')->first();
        $this->assertSame('Bruno Admin', $added->name);
        $this->assertTrue(Hash::check('secret-bruno', $added->password));

        $this->actingAs($admin, 'admin')->post('/admin/proprietes/administrateurs', [
            'new_admin_name' => 'Doublon',
            'new_admin_email' => 'moi@example.com',
            'new_admin_password' => 'secret-doublon',
            'new_admin_password_confirmation' => 'secret-doublon',
        ])->assertSessionHasErrors('new_admin_email');
    }

    public function test_a_guest_cannot_reach_the_admin_properties_page(): void
    {
        $this->get('/admin/proprietes')->assertRedirect(route('admin.login'));
        $this->post('/admin/proprietes/administrateurs', [])->assertRedirect(route('admin.login'));
    }

    public function test_an_admin_can_remove_another_admin_but_not_themselves(): void
    {
        $admin = Admin::factory()->create();
        $other = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->delete("/admin/proprietes/administrateurs/{$admin->id}")
            ->assertForbidden();
        $this->assertModelExists($admin);

        $this->actingAs($admin, 'admin')->delete("/admin/proprietes/administrateurs/{$other->id}")
            ->assertRedirect(route('admin.properties'));
        $this->assertModelMissing($other);
    }
}
