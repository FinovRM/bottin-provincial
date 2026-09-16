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

    public function test_a_regional_responsable_only_sees_members_of_its_own_subtree(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');

        $response = $this->actingAs($tree['region1'])->get('/tableau-de-bord/membres');

        $response->assertOk();
        $response->assertSee('Membre Local 1');
        $response->assertDontSee('Membre Local 3');
    }

    public function test_a_member_sees_siblings_of_its_own_organization_and_its_subtree(): void
    {
        $tree = $this->tree();
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $memberOfLocal2 = $this->addRole($tree['local2'], 'Membre Local 2', 'l2@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');

        $url = URL::temporarySignedRoute('member-login.consume', now()->addMinutes(15), ['member' => $memberOfLocal2->id]);
        $this->get($url);

        $response = $this->get('/membre/tableau-de-bord');

        $response->assertOk();
        // sibling org (same level, same parent as Local 2) is visible
        $response->assertSee('Membre Local 1');
        $response->assertSee('Membre Local 2');
        // a different branch of the tree is not
        $response->assertDontSee('Membre Local 3');
    }

    public function test_a_member_of_a_regional_organization_also_sees_its_own_subtree(): void
    {
        $tree = $this->tree();
        $memberOfRegion1 = $this->addRole($tree['region1'], 'Membre Région 1', 'r1@example.com');
        $this->addRole($tree['local1'], 'Membre Local 1', 'l1@example.com');
        $this->addRole($tree['local3'], 'Membre Local 3', 'l3@example.com');

        $url = URL::temporarySignedRoute('member-login.consume', now()->addMinutes(15), ['member' => $memberOfRegion1->id]);
        $this->get($url);

        $response = $this->get('/membre/tableau-de-bord');

        $response->assertOk();
        $response->assertSee('Membre Région 1');
        $response->assertSee('Membre Local 1');
        $response->assertDontSee('Membre Local 3');
    }
}
