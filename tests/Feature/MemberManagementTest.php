<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_organization_can_add_a_role_to_itself(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($organization)->post('/membres', [
            'role' => 'Trésorier',
            'name' => 'Jeanne Tremblay',
            'email' => 'jeanne@example.com',
            'cell_phone' => '514-555-1234',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('members', ['email' => 'jeanne@example.com', 'name' => 'Jeanne Tremblay']);
        $this->assertDatabaseHas('member_roles', [
            'organization_id' => $organization->id,
            'role' => 'Trésorier',
        ]);
    }

    public function test_a_new_role_is_always_attached_to_the_acting_organization(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();

        $response = $this->actingAs($regional)->post('/membres', [
            'role' => 'Trésorier',
            'name' => 'Jeanne Tremblay',
            'email' => 'jeanne@example.com',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('member_roles', ['organization_id' => $regional->id, 'role' => 'Trésorier']);
    }

    public function test_adding_a_second_role_for_an_existing_email_keeps_name_and_cell_phone_constant(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();

        $member = Member::create(['name' => 'Jeanne Tremblay', 'email' => 'jeanne@example.com', 'cell_phone' => '514-555-1234']);
        $provincial->memberRoles()->create(['member_id' => $member->id, 'role' => 'Trésorière']);

        $response = $this->actingAs($regional)->post('/membres', [
            'role' => 'Bénévole',
            'name' => 'Nom différent',
            'email' => 'jeanne@example.com',
            'cell_phone' => '999-999-9999',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseCount('members', 1);
        $this->assertDatabaseHas('members', [
            'email' => 'jeanne@example.com',
            'name' => 'Jeanne Tremblay',
            'cell_phone' => '514-555-1234',
        ]);
        $this->assertDatabaseHas('member_roles', ['member_id' => $member->id, 'organization_id' => $regional->id, 'role' => 'Bénévole']);
        $this->assertDatabaseHas('member_roles', ['member_id' => $member->id, 'organization_id' => $provincial->id, 'role' => 'Trésorière']);
    }

    public function test_an_organization_can_update_the_role_of_its_own_member(): void
    {
        $organization = Organization::factory()->provincial()->create();
        $member = Member::create(['name' => 'Ancien nom', 'email' => 'membre@example.com']);
        $memberRole = $organization->memberRoles()->create(['member_id' => $member->id, 'role' => 'Bénévole']);

        $response = $this->actingAs($organization)->put("/membres/{$memberRole->id}", [
            'role' => 'Trésorier',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('member_roles', ['id' => $memberRole->id, 'role' => 'Trésorier']);
        $this->assertDatabaseHas('members', ['id' => $member->id, 'name' => 'Ancien nom']);
    }

    public function test_an_organization_cannot_update_another_organizations_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $member = Member::create(['name' => 'Membre régional', 'email' => 'membre-regional@example.com']);
        $memberRole = $regional->memberRoles()->create(['member_id' => $member->id, 'role' => 'Bénévole']);

        $response = $this->actingAs($provincial)->put("/membres/{$memberRole->id}", [
            'role' => 'Piraté',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('member_roles', ['id' => $memberRole->id, 'role' => 'Piraté']);
    }

    public function test_an_organization_can_delete_a_role_of_its_own_member(): void
    {
        $organization = Organization::factory()->provincial()->create();
        $member = Member::create(['name' => 'À retirer', 'email' => 'retirer@example.com']);
        $memberRole = $organization->memberRoles()->create(['member_id' => $member->id, 'role' => 'Bénévole']);

        $response = $this->actingAs($organization)->delete("/membres/{$memberRole->id}");

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseMissing('member_roles', ['id' => $memberRole->id]);
    }

    public function test_deleting_a_members_last_role_removes_the_person(): void
    {
        $organization = Organization::factory()->provincial()->create();
        $member = Member::create(['name' => 'Seul rôle', 'email' => 'seul@example.com']);
        $memberRole = $organization->memberRoles()->create(['member_id' => $member->id, 'role' => 'Bénévole']);

        $this->actingAs($organization)->delete("/membres/{$memberRole->id}");

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    public function test_deleting_one_of_two_roles_keeps_the_person_and_the_other_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $member = Member::create(['name' => 'Double rôle', 'email' => 'double@example.com']);
        $roleAtProvincial = $provincial->memberRoles()->create(['member_id' => $member->id, 'role' => 'Direction']);
        $roleAtRegional = $regional->memberRoles()->create(['member_id' => $member->id, 'role' => 'Bénévole']);

        $this->actingAs($provincial)->delete("/membres/{$roleAtProvincial->id}");

        $this->assertDatabaseHas('members', ['id' => $member->id]);
        $this->assertDatabaseHas('member_roles', ['id' => $roleAtRegional->id]);
        $this->assertDatabaseMissing('member_roles', ['id' => $roleAtProvincial->id]);
    }
}
