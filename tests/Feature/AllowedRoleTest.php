<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowedRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_organization_can_add_an_allowed_role_for_its_children(): void
    {
        $provincial = Organization::factory()->provincial()->create();

        $response = $this->actingAs($provincial)->post('/profil/roles-permis', [
            'name' => 'Bénévole',
            'group' => 'organisation',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('allowed_roles', [
            'organization_id' => $provincial->id,
            'name' => 'Bénévole',
            'group' => 'organisation',
        ]);
    }

    public function test_a_local_organization_cannot_add_an_allowed_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $local = Organization::factory()->local($regional)->create();

        $response = $this->actingAs($local)->post('/profil/roles-permis', [
            'name' => 'Bénévole',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('allowed_roles', ['name' => 'Bénévole']);
    }

    public function test_a_parent_organization_cannot_add_a_duplicate_allowed_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $provincial->allowedRoles()->create(['name' => 'Bénévole']);

        $response = $this->actingAs($provincial)->post('/profil/roles-permis', [
            'name' => 'Bénévole',
            'group' => 'organisation',
        ]);

        $response->assertSessionHasErrorsIn('allowed-role-add-organisation', 'name');
        $this->assertDatabaseCount('allowed_roles', 1);
    }

    public function test_a_parent_organization_can_delete_its_own_allowed_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $allowedRole = $provincial->allowedRoles()->create(['name' => 'Bénévole']);

        $response = $this->actingAs($provincial)->delete("/profil/roles-permis/{$allowedRole->id}");

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseMissing('allowed_roles', ['id' => $allowedRole->id]);
    }

    public function test_an_organization_cannot_delete_another_organizations_allowed_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $otherProvincial = Organization::factory()->provincial()->create();
        $allowedRole = $otherProvincial->allowedRoles()->create(['name' => 'Bénévole']);

        $response = $this->actingAs($provincial)->delete("/profil/roles-permis/{$allowedRole->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('allowed_roles', ['id' => $allowedRole->id]);
    }

    public function test_deleting_an_allowed_role_removes_it_from_every_child_member(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $allowedRole = $provincial->allowedRoles()->create(['name' => 'Bénévole']);
        $regional = Organization::factory()->regional($provincial)->create();
        $member = Member::create(['name' => 'Membre', 'email' => 'membre@example.com']);
        $regional->memberRoles()->create(['member_id' => $member->id, 'role' => 'Bénévole']);

        $response = $this->actingAs($provincial)->delete("/profil/roles-permis/{$allowedRole->id}");

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseMissing('allowed_roles', ['id' => $allowedRole->id]);
        $this->assertDatabaseMissing('member_roles', ['organization_id' => $regional->id, 'role' => 'Bénévole']);
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    public function test_renaming_an_allowed_role_renames_it_on_every_child_member(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $allowedRole = $provincial->allowedRoles()->create(['name' => 'Bénévole']);
        $regional = Organization::factory()->regional($provincial)->create();
        $member = Member::create(['name' => 'Membre', 'email' => 'membre@example.com']);
        $regional->memberRoles()->create(['member_id' => $member->id, 'role' => 'Bénévole']);

        $response = $this->actingAs($provincial)->put("/profil/roles-permis/{$allowedRole->id}", [
            'name' => 'Bénévole occasionnel',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('allowed_roles', ['id' => $allowedRole->id, 'name' => 'Bénévole occasionnel']);
        $this->assertDatabaseHas('member_roles', ['organization_id' => $regional->id, 'role' => 'Bénévole occasionnel']);
        $this->assertDatabaseMissing('member_roles', ['organization_id' => $regional->id, 'role' => 'Bénévole']);
    }

    public function test_the_properties_page_shows_allowed_roles_only_for_a_parent_organization(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $provincial->allowedRoles()->create(['name' => 'Bénévole']);

        $response = $this->actingAs($provincial)->get('/proprietes');

        $response->assertOk();
        $response->assertSee('Rôles permis');
        $response->assertSee('Bénévole');
    }

    public function test_the_properties_page_hides_allowed_roles_for_a_local_organization(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $local = Organization::factory()->local($regional)->create();

        $response = $this->actingAs($local)->get('/proprietes');

        $response->assertOk();
        $response->assertDontSee('Rôles permis');
    }
}
