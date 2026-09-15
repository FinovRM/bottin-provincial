<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_organization_can_add_a_member_to_itself(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($organization)->post('/membres', [
            'role' => 'Trésorier',
            'name' => 'Jeanne Tremblay',
            'email' => 'jeanne@example.com',
            'cell_phone' => '514-555-1234',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('members', [
            'organization_id' => $organization->id,
            'email' => 'jeanne@example.com',
        ]);
    }

    public function test_a_new_member_is_always_attached_to_the_acting_organization(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();

        $response = $this->actingAs($regional)->post('/membres', [
            'role' => 'Trésorier',
            'name' => 'Jeanne Tremblay',
            'email' => 'jeanne@example.com',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('members', [
            'organization_id' => $regional->id,
            'email' => 'jeanne@example.com',
        ]);
    }

    public function test_an_organization_can_update_its_own_member(): void
    {
        $organization = Organization::factory()->provincial()->create();
        $member = $organization->members()->create([
            'role' => 'Bénévole',
            'name' => 'Ancien nom',
            'email' => 'membre@example.com',
        ]);

        $response = $this->actingAs($organization)->put("/membres/{$member->id}", [
            'role' => 'Bénévole',
            'name' => 'Nouveau nom',
            'email' => 'membre@example.com',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('members', ['id' => $member->id, 'name' => 'Nouveau nom']);
    }

    public function test_an_organization_cannot_update_another_organizations_member(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $member = $regional->members()->create([
            'role' => 'Bénévole',
            'name' => 'Membre régional',
            'email' => 'membre-regional@example.com',
        ]);

        $response = $this->actingAs($provincial)->put("/membres/{$member->id}", [
            'role' => 'Bénévole',
            'name' => 'Piraté',
            'email' => 'membre-regional@example.com',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('members', ['id' => $member->id, 'name' => 'Piraté']);
    }

    public function test_an_organization_can_delete_its_own_member(): void
    {
        $organization = Organization::factory()->provincial()->create();
        $member = $organization->members()->create([
            'role' => 'Bénévole',
            'name' => 'À retirer',
            'email' => 'retirer@example.com',
        ]);

        $response = $this->actingAs($organization)->delete("/membres/{$member->id}");

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }
}
