<?php

namespace Tests\Feature;

use App\Models\Member;
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

        $response->assertSessionHasErrorsIn('minimum-role-add', 'name');
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

    public function test_deleting_a_minimum_role_removes_it_from_every_child_member(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $minimumRole = $provincial->minimumRoles()->create(['name' => 'Président']);
        $regional = Organization::factory()->regional($provincial)->create();

        $onlyRole = Member::create(['name' => 'Seul Rôle', 'email' => 'seul@example.com']);
        $regional->memberRoles()->create(['member_id' => $onlyRole->id, 'role' => 'Président']);

        $twoRoles = Member::create(['name' => 'Deux Rôles', 'email' => 'deux@example.com']);
        $regional->memberRoles()->create(['member_id' => $twoRoles->id, 'role' => 'Président']);
        $regional->memberRoles()->create(['member_id' => $twoRoles->id, 'role' => 'Trésorier']);

        $response = $this->actingAs($provincial)->delete("/profil/roles-minimum/{$minimumRole->id}");

        $response->assertRedirect(route('profile'));
        $this->assertDatabaseMissing('minimum_roles', ['id' => $minimumRole->id]);
        $this->assertDatabaseMissing('member_roles', ['organization_id' => $regional->id, 'role' => 'Président']);
        // Had only that role: the member itself is removed.
        $this->assertDatabaseMissing('members', ['id' => $onlyRole->id]);
        // Had another role too: the member stays, with only the remaining role.
        $this->assertDatabaseHas('members', ['id' => $twoRoles->id]);
        $this->assertDatabaseHas('member_roles', ['organization_id' => $regional->id, 'role' => 'Trésorier']);
    }

    public function test_renaming_a_minimum_role_renames_it_on_every_child_member(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $minimumRole = $provincial->minimumRoles()->create(['name' => 'Président']);
        $regional = Organization::factory()->regional($provincial)->create();
        $member = Member::create(['name' => 'Membre', 'email' => 'membre@example.com']);
        $regional->memberRoles()->create(['member_id' => $member->id, 'role' => 'Président']);

        $response = $this->actingAs($provincial)->put("/profil/roles-minimum/{$minimumRole->id}", [
            'name' => 'Président du CA',
        ]);

        $response->assertRedirect(route('profile'));
        $this->assertDatabaseHas('minimum_roles', ['id' => $minimumRole->id, 'name' => 'Président du CA']);
        $this->assertDatabaseHas('member_roles', ['organization_id' => $regional->id, 'role' => 'Président du CA']);
        $this->assertDatabaseMissing('member_roles', ['organization_id' => $regional->id, 'role' => 'Président']);
    }

    public function test_renaming_a_minimum_role_to_an_existing_name_shows_a_scoped_error(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $provincial->minimumRoles()->create(['name' => 'Président']);
        $treasurer = $provincial->minimumRoles()->create(['name' => 'Trésorier']);

        $response = $this->actingAs($provincial)->put("/profil/roles-minimum/{$treasurer->id}", [
            'name' => 'Président',
        ]);

        $response->assertSessionHasErrorsIn("minimum-role-{$treasurer->id}", 'name');
        $this->assertDatabaseHas('minimum_roles', ['id' => $treasurer->id, 'name' => 'Trésorier']);
    }

    public function test_deleting_a_minimum_role_never_touches_grandchild_organizations(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $minimumRole = $provincial->minimumRoles()->create(['name' => 'Président']);
        $regional = Organization::factory()->regional($provincial)->create();
        $local = Organization::factory()->local($regional)->create();

        // Same role name, but Local is a grandchild of Provincial (child of Regional),
        // so Provincial's minimum roles must never reach it.
        $member = Member::create(['name' => 'Membre Local', 'email' => 'local@example.com']);
        $local->memberRoles()->create(['member_id' => $member->id, 'role' => 'Président']);

        $this->actingAs($provincial)->delete("/profil/roles-minimum/{$minimumRole->id}");

        $this->assertDatabaseHas('member_roles', ['organization_id' => $local->id, 'role' => 'Président']);
        $this->assertDatabaseHas('members', ['id' => $member->id]);
    }

    public function test_renaming_a_minimum_role_never_touches_grandchild_organizations(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $minimumRole = $provincial->minimumRoles()->create(['name' => 'Président']);
        $regional = Organization::factory()->regional($provincial)->create();
        $local = Organization::factory()->local($regional)->create();

        $member = Member::create(['name' => 'Membre Local', 'email' => 'local@example.com']);
        $local->memberRoles()->create(['member_id' => $member->id, 'role' => 'Président']);

        $this->actingAs($provincial)->put("/profil/roles-minimum/{$minimumRole->id}", [
            'name' => 'Président du CA',
        ]);

        $this->assertDatabaseHas('member_roles', ['organization_id' => $local->id, 'role' => 'Président']);
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
