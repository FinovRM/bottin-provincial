<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationGroupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * provincial
     *   └─ regional (Organisations group)
     *        ├─ localOrg (Organisations group)
     *        └─ localLeague (Ligues group)
     *
     * @return array<string, Organization>
     */
    private function tree(): array
    {
        $provincial = Organization::factory()->provincial()->create(['name' => 'Provincial']);
        $regional = Organization::factory()->regional($provincial)->create(['name' => 'Régional']);
        $localOrg = Organization::factory()->local($regional)->create(['name' => 'AHM Local']);
        $localLeague = Organization::factory()->local($regional)->ligue()->create(['name' => 'Ligue Locale']);

        return compact('provincial', 'regional', 'localOrg', 'localLeague');
    }

    private function addRole(Organization $organization, string $name, string $email, string $role = 'Bénévole'): Member
    {
        $member = Member::create(['name' => $name, 'email' => $email]);
        $organization->memberRoles()->create(['member_id' => $member->id, 'role' => $role]);

        return $member;
    }

    public function test_a_parent_organization_can_create_a_league_child(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($tree['regional'])->post('/organisations', [
            'name' => 'Ligue Yamaska',
            'group' => 'ligue',
            'responsable_name' => 'Resp Ligue',
            'responsable_email' => 'ligue-yamaska@example.com',
            'responsable_email_confirmation' => 'ligue-yamaska@example.com',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('organizations', [
            'name' => 'Ligue Yamaska',
            'parent_id' => $tree['regional']->id,
            'group' => 'ligue',
        ]);
    }

    public function test_the_properties_page_splits_children_into_two_group_sections(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($tree['regional'])->get('/tableau-de-bord/proprietes');

        $response->assertOk();
        $response->assertSeeInOrder(['Organisations enfant', 'AHM Local', 'Ligues enfant', 'Ligue Locale']);
    }

    public function test_the_bottin_scopes_a_member_to_their_own_group(): void
    {
        $tree = $this->tree();
        $orgMember = $this->addRole($tree['localOrg'], 'Membre Organisation', 'org@example.com');
        $leagueMember = $this->addRole($tree['localLeague'], 'Membre Ligue', 'ligue@example.com');

        $response = $this->actingAs($tree['localOrg'])->get('/bottin');

        $response->assertOk();
        $response->assertSee('Membre Organisation');
        $response->assertDontSee('Membre Ligue');
    }

    public function test_the_bottin_scopes_a_league_member_to_their_own_group_too(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['localOrg'], 'Membre Organisation', 'org@example.com');
        $this->addRole($tree['localLeague'], 'Membre Ligue', 'ligue@example.com');

        $response = $this->actingAs($tree['localLeague'])->get('/bottin');

        $response->assertOk();
        $response->assertSee('Membre Ligue');
        $response->assertDontSee('Membre Organisation');
    }

    public function test_bottin_des_membres_stays_cumulative_across_groups(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['localOrg'], 'Membre Organisation', 'org@example.com');
        $this->addRole($tree['localLeague'], 'Membre Ligue', 'ligue@example.com');

        $response = $this->actingAs($tree['regional'])->get('/tableau-de-bord/membres');

        $response->assertOk();
        $response->assertSee('Membre Organisation');
        $response->assertSee('Membre Ligue');
    }

    public function test_bottin_des_organisations_stays_cumulative_across_groups(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($tree['regional'])->get('/tableau-de-bord/organisations');

        $response->assertOk();
        $response->assertSee('AHM Local');
        $response->assertSee('Ligue Locale');
    }

    public function test_personal_filters_picker_includes_both_groups(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($tree['regional'])->get('/profil');

        $response->assertOk();
        $response->assertSee('AHM Local');
        $response->assertSee('Ligue Locale');
    }

    public function test_the_profile_page_shows_a_separate_section_for_leagues(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($tree['regional'])->get('/profil');

        $response->assertOk();
        $response->assertSee('Rôles de mes organisations enfant');
        $response->assertSee('Rôles de mes ligues enfant');
    }

    public function test_a_minimum_role_for_one_group_does_not_restrict_the_other_group(): void
    {
        $tree = $this->tree();
        $tree['regional']->allowedRoles()->create(['name' => 'Arbitre', 'group' => 'ligue']);
        $tree['regional']->allowedRoles()->create(['name' => 'Bénévole', 'group' => 'organisation']);

        // The league child can only use its own group's role.
        $rejected = $this->actingAs($tree['localLeague'])->post('/membres', [
            'role' => 'Bénévole',
            'name' => 'Jeanne',
            'email' => 'jeanne@example.com',
            'email_confirmation' => 'jeanne@example.com',
        ]);
        $rejected->assertSessionHasErrors('role');

        $accepted = $this->actingAs($tree['localLeague'])->post('/membres', [
            'role' => 'Arbitre',
            'name' => 'Jeanne',
            'email' => 'jeanne@example.com',
            'email_confirmation' => 'jeanne@example.com',
        ]);
        $accepted->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('member_roles', ['organization_id' => $tree['localLeague']->id, 'role' => 'Arbitre']);
    }

    public function test_deleting_a_league_minimum_role_does_not_affect_organisation_group_children(): void
    {
        $tree = $this->tree();
        $minimumRole = $tree['regional']->minimumRoles()->create(['name' => 'Président', 'group' => 'ligue']);

        $orgMember = $this->addRole($tree['localOrg'], 'Membre Organisation', 'org@example.com', 'Président');
        $leagueMember = $this->addRole($tree['localLeague'], 'Membre Ligue', 'ligue@example.com', 'Président');

        $this->actingAs($tree['regional'])->delete("/profil/roles-minimum/{$minimumRole->id}");

        // League group: removed.
        $this->assertDatabaseMissing('member_roles', ['organization_id' => $tree['localLeague']->id, 'role' => 'Président']);
        // Organisations group: untouched.
        $this->assertDatabaseHas('member_roles', ['organization_id' => $tree['localOrg']->id, 'role' => 'Président']);
    }
}
