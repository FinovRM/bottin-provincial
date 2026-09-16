<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class QueryScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build the reference tree used by every scope test:
     *
     * provincial
     *   ├─ region1
     *   │    ├─ local1
     *   │    └─ local2
     *   └─ region2
     *        └─ local3
     *
     * @return array<string, Organization>
     */
    private function tree(): array
    {
        $provincial = Organization::factory()->provincial()->create(['name' => 'Provincial']);
        $region1 = Organization::factory()->regional($provincial)->create(['name' => 'Région 1']);
        $region2 = Organization::factory()->regional($provincial)->create(['name' => 'Région 2']);
        $local1 = Organization::factory()->local($region1)->create(['name' => 'Local 1']);
        $local2 = Organization::factory()->local($region1)->create(['name' => 'Local 2']);
        $local3 = Organization::factory()->local($region2)->create(['name' => 'Local 3']);

        return compact('provincial', 'region1', 'region2', 'local1', 'local2', 'local3');
    }

    private function addRole(Organization $organization, string $name, string $email): Member
    {
        $member = Member::create(['name' => $name, 'email' => $email]);
        $organization->memberRoles()->create(['member_id' => $member->id, 'role' => 'Bénévole']);

        return $member;
    }

    public function test_a_regional_responsable_only_sees_its_own_subtree_in_the_organization_query(): void
    {
        $tree = $this->tree();

        $response = $this->actingAs($tree['region1'])->get('/tableau-de-bord/organisations');

        $response->assertOk();
        $response->assertSee('Région 1');
        $response->assertSee('Local 1');
        $response->assertSee('Local 2');
        $response->assertDontSee('Local 3');
        $response->assertDontSee('Région 2');
        $response->assertDontSee('Provincial');
    }

    public function test_a_regional_responsable_sees_members_of_every_region_and_local_organization_but_not_provincial(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');
        $this->addRole($tree['region2'], 'Membre Région 2', 'r2@example.com');
        $this->addRole($tree['provincial'], 'Membre Provincial', 'p1@example.com');

        $response = $this->actingAs($tree['region1'])->get('/tableau-de-bord/membres');

        $response->assertOk();
        // every local organization, anywhere in the tree, is visible
        $response->assertSee('Membre Local 1');
        $response->assertSee('Membre Local 3');
        // every other region is visible too
        $response->assertSee('Membre Région 2');
        // provincial, one level above regional, is not shown by default —
        // only "ma direction" surfaces it (see the my_direction tests below)
        $response->assertDontSee('Membre Provincial');
    }

    public function test_the_region_filter_isolates_one_regions_local_organizations_for_a_responsable(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');

        $response = $this->actingAs($tree['region1'])->get('/tableau-de-bord/membres?region_id='.$tree['region1']->id);

        $response->assertOk();
        $response->assertSee('Membre Local 1');
        $response->assertDontSee('Membre Local 3');
    }

    public function test_a_local_member_sees_every_local_organization_but_not_regional_or_provincial_by_default(): void
    {
        $tree = $this->tree();
        $memberOfLocal2 = $this->addRole($tree['local2'], 'Membre Local 2', 'l2@example.com');
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');
        $this->addRole($tree['region1'], 'Membre Région 1', 'r1@example.com');
        $this->addRole($tree['region2'], 'Membre Région 2', 'r2@example.com');
        $this->addRole($tree['provincial'], 'Membre Provincial', 'p1@example.com');

        $url = URL::temporarySignedRoute('member-login.consume', now()->addMinutes(15), ['member' => $memberOfLocal2->id]);
        $this->get($url);

        $response = $this->get('/membre/tableau-de-bord');

        $response->assertOk();
        // every local organization, anywhere in the tree, is visible
        $response->assertSee('Membre Local 1');
        $response->assertSee('Membre Local 2');
        $response->assertSee('Membre Local 3');
        // nothing above local is shown by default, including its own regional
        // direction — that only appears via "ma direction" (see below)
        $response->assertDontSee('Membre Région 1');
        $response->assertDontSee('Membre Région 2');
        $response->assertDontSee('Membre Provincial');
    }

    public function test_a_local_member_can_filter_by_a_region_it_has_no_direct_visibility_into(): void
    {
        $tree = $this->tree();
        $memberOfLocal2 = $this->addRole($tree['local2'], 'Membre Local 2', 'l2@example.com');
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');

        $url = URL::temporarySignedRoute('member-login.consume', now()->addMinutes(15), ['member' => $memberOfLocal2->id]);
        $this->get($url);

        // region2 is not this member's own direction (that's region1) — the filter
        // should still list it, since it has locals the member can already see.
        $response = $this->get('/membre/tableau-de-bord');
        $response->assertOk();
        $response->assertSee('Région 2');

        $filtered = $this->get('/membre/tableau-de-bord?region_id='.$tree['region2']->id);
        $filtered->assertOk();
        $filtered->assertSee('Membre Local 3');
        $filtered->assertDontSee('Membre Local 1');
        $filtered->assertDontSee('Membre Local 2');
    }

    public function test_a_regional_member_sees_every_region_and_every_local_organization_but_not_provincial_by_default(): void
    {
        $tree = $this->tree();
        $memberOfRegion1 = $this->addRole($tree['region1'], 'Membre Région 1', 'r1@example.com');
        $this->addRole($tree['region2'], 'Membre Région 2', 'r2@example.com');
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');
        $this->addRole($tree['provincial'], 'Membre Provincial', 'p1@example.com');

        $url = URL::temporarySignedRoute('member-login.consume', now()->addMinutes(15), ['member' => $memberOfRegion1->id]);
        $this->get($url);

        $response = $this->get('/membre/tableau-de-bord');

        $response->assertOk();
        // its own region and every other region are visible
        $response->assertSee('Membre Région 1');
        $response->assertSee('Membre Région 2');
        // every local organization, anywhere in the tree, is visible
        $response->assertSee('Membre Local 1');
        $response->assertSee('Membre Local 3');
        // provincial, one level above regional, is not shown by default
        $response->assertDontSee('Membre Provincial');
    }

    public function test_the_region_filter_isolates_one_regions_local_organizations_for_a_member(): void
    {
        $tree = $this->tree();
        $memberOfRegion1 = $this->addRole($tree['region1'], 'Membre Région 1', 'r1@example.com');
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');

        $url = URL::temporarySignedRoute('member-login.consume', now()->addMinutes(15), ['member' => $memberOfRegion1->id]);
        $this->get($url);

        $response = $this->get('/membre/tableau-de-bord?region_id='.$tree['region1']->id);

        $response->assertOk();
        $response->assertSee('Membre Local 1');
        $response->assertDontSee('Membre Local 3');
    }

    public function test_the_my_direction_filter_isolates_a_members_own_parent_organization(): void
    {
        $tree = $this->tree();
        $memberOfLocal2 = $this->addRole($tree['local2'], 'Membre Local 2', 'l2@example.com');
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['region1'], 'Membre Région 1', 'r1@example.com');
        $this->addRole($tree['region2'], 'Membre Région 2', 'r2@example.com');

        $url = URL::temporarySignedRoute('member-login.consume', now()->addMinutes(15), ['member' => $memberOfLocal2->id]);
        $this->get($url);

        $response = $this->get('/membre/tableau-de-bord?my_direction=1');

        $response->assertOk();
        $response->assertSee('Membre Région 1');
        $response->assertDontSee('Membre Local 1');
        $response->assertDontSee('Membre Local 2');
        $response->assertDontSee('Membre Région 2');
    }

    public function test_the_my_direction_filter_is_hidden_for_a_provincial_member(): void
    {
        $tree = $this->tree();
        $memberOfProvincial = $this->addRole($tree['provincial'], 'Membre Provincial', 'p1@example.com');

        $url = URL::temporarySignedRoute('member-login.consume', now()->addMinutes(15), ['member' => $memberOfProvincial->id]);
        $this->get($url);

        $response = $this->get('/membre/tableau-de-bord');

        $response->assertOk();
        $response->assertDontSee('Ma direction');
    }

    public function test_the_my_direction_filter_isolates_a_responsables_own_parent_organization(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['region1'], 'Membre Région 1', 'r1@example.com');
        $this->addRole($tree['region2'], 'Membre Région 2', 'r2@example.com');

        $response = $this->actingAs($tree['local1'])->get('/tableau-de-bord/membres?my_direction=1');

        $response->assertOk();
        $response->assertSee('Membre Région 1');
        $response->assertDontSee('Membre Local 1');
        $response->assertDontSee('Membre Région 2');
    }
}
