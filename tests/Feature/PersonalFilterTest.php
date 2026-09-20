<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, Organization>
     */
    private function tree(): array
    {
        $provincial = Organization::factory()->provincial()->create(['name' => 'Provincial']);
        $region1 = Organization::factory()->regional($provincial)->create(['name' => 'Région 1']);
        $region2 = Organization::factory()->regional($provincial)->create(['name' => 'Région 2']);
        $local1 = Organization::factory()->local($region1)->create(['name' => 'Local 1']);
        $local3 = Organization::factory()->local($region2)->create(['name' => 'Local 3']);

        return compact('provincial', 'region1', 'region2', 'local1', 'local3');
    }

    private function addRole(Organization $organization, string $name, string $email, string $role = 'Bénévole'): Member
    {
        $member = Member::create(['name' => $name, 'email' => $email]);
        $organization->memberRoles()->create(['member_id' => $member->id, 'role' => $role]);

        return $member;
    }

    public function test_a_responsable_can_create_a_personal_filter_from_the_profile_page(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($tree['provincial'])->post('/profil/filtres', [
            'name' => 'Mon filtre',
            'description' => 'Une description',
            'region_ids' => [$tree['region1']->id],
            'roles' => ['Bénévole'],
        ]);

        $response->assertRedirect(route('profile'));
        $this->assertDatabaseHas('personal_filters', [
            'filterable_type' => Organization::class,
            'filterable_id' => $tree['provincial']->id,
            'name' => 'Mon filtre',
            'description' => 'Une description',
        ]);
    }

    public function test_a_responsable_can_delete_their_own_personal_filter(): void
    {
        $tree = $this->tree();
        $filter = $tree['provincial']->personalFilters()->create(['name' => 'À retirer']);

        $response = $this->actingAs($tree['provincial'])->delete("/profil/filtres/{$filter->id}");

        $response->assertRedirect(route('profile'));
        $this->assertDatabaseMissing('personal_filters', ['id' => $filter->id]);
    }

    public function test_the_personal_filters_dropdown_shows_on_the_bottin_page_even_with_no_saved_filters(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($tree['provincial'])->get('/bottin');

        $response->assertOk();
        $response->assertSee('Filtres personnels');
    }

    public function test_a_responsable_can_update_their_own_personal_filters_selections(): void
    {
        $tree = $this->tree();
        $filter = $tree['provincial']->personalFilters()->create([
            'name' => 'Mon filtre',
            'region_ids' => [$tree['region1']->id],
        ]);

        $response = $this->actingAs($tree['provincial'])->put("/profil/filtres/{$filter->id}", [
            'region_ids' => [$tree['region2']->id],
            'local_ids' => [$tree['local3']->id],
            'roles' => ['Trésorier'],
        ]);

        $response->assertRedirect(route('profile'));
        $filter->refresh();
        $this->assertSame('Mon filtre', $filter->name);
        $this->assertEquals([$tree['region2']->id], array_map('intval', $filter->region_ids));
        $this->assertEquals([$tree['local3']->id], array_map('intval', $filter->local_ids));
        $this->assertSame(['Trésorier'], $filter->roles);
    }

    public function test_updating_a_personal_filter_with_no_selections_clears_previous_ones(): void
    {
        $tree = $this->tree();
        $filter = $tree['provincial']->personalFilters()->create([
            'name' => 'Mon filtre',
            'region_ids' => [$tree['region1']->id],
            'roles' => ['Bénévole'],
        ]);

        $response = $this->actingAs($tree['provincial'])->put("/profil/filtres/{$filter->id}", []);

        $response->assertRedirect(route('profile'));
        $filter->refresh();
        $this->assertSame([], $filter->region_ids);
        $this->assertSame([], $filter->roles);
    }

    public function test_a_responsable_cannot_update_someone_elses_personal_filter(): void
    {
        $tree = $this->tree();
        $otherOrganization = Organization::factory()->provincial()->create();
        $filter = $otherOrganization->personalFilters()->create([
            'name' => 'Pas le mien',
            'region_ids' => [$tree['region1']->id],
        ]);

        $response = $this->actingAs($tree['provincial'])->put("/profil/filtres/{$filter->id}", [
            'region_ids' => [$tree['region2']->id],
        ]);

        $response->assertForbidden();
        $this->assertEquals([$tree['region1']->id], array_map('intval', $filter->fresh()->region_ids));
    }

    public function test_a_responsable_cannot_delete_someone_elses_personal_filter(): void
    {
        $tree = $this->tree();
        $otherOrganization = Organization::factory()->provincial()->create();
        $filter = $otherOrganization->personalFilters()->create(['name' => 'Pas le mien']);

        $response = $this->actingAs($tree['provincial'])->delete("/profil/filtres/{$filter->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('personal_filters', ['id' => $filter->id]);
    }

    public function test_applying_a_personal_filter_narrows_the_bottin_listing(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');

        $filter = $tree['provincial']->personalFilters()->create([
            'name' => 'Région 1 seulement',
            'region_ids' => [$tree['region1']->id],
        ]);

        $response = $this->actingAs($tree['provincial'])->get("/bottin?personal_filter_id={$filter->id}");

        $response->assertOk();
        $response->assertSee('Membre Local 1');
        $response->assertDontSee('Membre Local 3');
    }

    public function test_applying_a_personal_filter_with_roles_narrows_by_role_too(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['local1'], 'Trésorier Local 1', 't1@example.com', 'Trésorier');
        $this->addRole($tree['local1'], 'Bénévole Local 1', 'b1@example.com', 'Bénévole');

        $filter = $tree['provincial']->personalFilters()->create([
            'name' => 'Trésoriers',
            'roles' => ['Trésorier'],
        ]);

        $response = $this->actingAs($tree['provincial'])->get("/bottin?personal_filter_id={$filter->id}");

        $response->assertOk();
        $response->assertSee('Trésorier Local 1');
        $response->assertDontSee('Bénévole Local 1');
    }

    public function test_a_personal_filter_cannot_be_applied_by_another_organization(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');
        $filter = $tree['provincial']->personalFilters()->create([
            'name' => 'Privé',
            'region_ids' => [$tree['region1']->id],
        ]);

        // A regional responsable sees every local across the whole tree by default —
        // if someone else's filter were silently applied, Local 3 would disappear.
        $response = $this->actingAs($tree['region2'])->get("/bottin?personal_filter_id={$filter->id}");

        $response->assertOk();
        $response->assertSee('Membre Local 1');
        $response->assertSee('Membre Local 3');
    }
}
