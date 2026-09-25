<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
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

        $response = $this->actingAs($tree['regional'])->get('/proprietes');

        $response->assertOk();
        $response->assertSeeInOrder(['Organisations enfant', 'AHM Local', 'Ligues enfant', 'Ligue Locale']);
    }

    public function test_the_bottin_shows_members_of_both_groups_at_the_same_level(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['localOrg'], 'Membre Organisation', 'org@example.com');
        $this->addRole($tree['localLeague'], 'Membre Ligue', 'ligue@example.com');

        $asOrg = $this->actingAs($this->viewerOf($tree['localOrg']), 'member')->get('/bottin');
        $asOrg->assertOk();
        $asOrg->assertSee('Membre Organisation');
        $asOrg->assertSee('Membre Ligue');

        $asLeague = $this->actingAs($this->viewerOf($tree['localLeague']), 'member')->get('/bottin');
        $asLeague->assertOk();
        $asLeague->assertSee('Membre Ligue');
        $asLeague->assertSee('Membre Organisation');
    }

    public function test_bottin_des_membres_stays_cumulative_across_groups(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['localOrg'], 'Membre Organisation', 'org@example.com');
        $this->addRole($tree['localLeague'], 'Membre Ligue', 'ligue@example.com');

        $response = $this->actingAs($this->viewerOf($tree['regional']), 'member')->get('/bottin');

        $response->assertOk();
        $response->assertSee('Membre Organisation');
        $response->assertSee('Membre Ligue');
    }

    public function test_bottin_des_organisations_stays_cumulative_across_groups(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($this->viewerOf($tree['regional']), 'member')->get('/bottin/organisations');

        $response->assertOk();
        $response->assertSee('AHM Local');
        $response->assertSee('Ligue Locale');
    }

    public function test_personal_filters_picker_includes_both_groups(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($this->viewerOf($tree['regional']), 'member')->get('/profil');

        $response->assertOk();
        $response->assertSee('AHM Local');
        $response->assertSee('Ligue Locale');
    }

    public function test_the_properties_page_shows_each_groups_roles_after_its_children(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($tree['regional'])->get('/proprietes');

        $response->assertOk();
        $response->assertSeeInOrder(['Organisations enfant', 'Rôles de mes organisations enfant', 'Ligues enfant', 'Rôles de mes ligues enfant']);
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

    public function test_the_organizations_directory_shows_both_groups_at_the_same_level(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($this->viewerOf($tree['localOrg']), 'member')->get('/bottin/organisations');

        $response->assertOk();
        $response->assertSee('AHM Local');
        $response->assertSee('Ligue Locale');
    }

    public function test_the_organizations_directory_is_accessible_to_a_member(): void
    {
        $tree = $this->tree();
        $viewer = $this->addRole($tree['localOrg'], 'Membre Organisation', 'org@example.com');
        $this->loginAsMember($viewer->email);

        $response = $this->get('/bottin/organisations');

        $response->assertOk();
        $response->assertSee('AHM Local');
        $response->assertSee('Ligue Locale');
    }

    public function test_the_organizations_directory_can_be_filtered_by_level(): void
    {
        $tree = $this->tree();
        $otherRegional = Organization::factory()->regional($tree['provincial'])->create(['name' => 'Autre Régional']);
        Organization::factory()->local($otherRegional)->create(['name' => 'Autre Local']);

        $card = fn (string $name) => 'text-gray-900">'.$name.'</p>';

        $this->actingAs($this->viewerOf($tree['provincial']), 'member')->get("/bottin/organisations?regional_id={$tree['regional']->id}")
            ->assertSee($card('Régional'), false)
            ->assertSee($card('AHM Local'), false)
            ->assertSee($card('Ligue Locale'), false)
            ->assertDontSee($card('Autre Local'), false)
            ->assertDontSee($card('Provincial'), false);

        $this->actingAs($this->viewerOf($tree['regional']), 'member')->get("/bottin/organisations?local_id={$tree['localLeague']->id}")
            ->assertSee($card('Ligue Locale'), false)
            ->assertDontSee($card('AHM Local'), false)
            ->assertSee('Réinitialiser');
    }

    public function test_the_identity_banner_is_shown_on_every_bottin_page_for_a_member(): void
    {
        $tree = $this->tree();
        $viewer = $this->addRole($tree['localOrg'], 'Membre Organisation', 'org@example.com');

        $this->loginAsMember($viewer->email);

        foreach (['/bottin', '/bottin/organisations', '/profil'] as $url) {
            $this->get($url)->assertOk()->assertSee('Visiteur :')->assertSee('Membre Organisation');
        }

    }

    public function test_the_identity_banner_is_shown_to_a_responsable_on_the_properties_page(): void
    {
        $tree = $this->tree();

        $this->actingAs($tree['localOrg'])->get('/proprietes')
            ->assertOk()
            ->assertSee('Visiteur :')
            ->assertSee('Responsable de bottin de AHM Local')
            // A responsable only works on their properties: no Organisations link.
            ->assertDontSee('href="'.route('bottin.organizations').'"', false);
    }

    public function test_the_organizations_nav_link_is_visible_to_a_member(): void
    {
        $tree = $this->tree();
        $viewer = $this->addRole($tree['localOrg'], 'Membre Organisation', 'org@example.com');

        $this->loginAsMember($viewer->email);
        $asMember = $this->get('/bottin');
        $asMember->assertSee(route('bottin.organizations'), false);
    }

    private function loginAsMember(string $email): void
    {
        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => $email]);
        $this->post($url, ['confirmed' => '1']);
    }
}
