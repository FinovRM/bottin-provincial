<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BottinHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_sees_organization_names_without_links(): void
    {
        $provincial = Organization::factory()->provincial()->create(['name' => 'Provincial X']);

        $response = $this->get('/');

        $response->assertSee('Provincial X');
        $response->assertDontSee(route('bottin.organization-members', $provincial), false);
        $response->assertDontSee('Visiteur :');
    }

    public function test_a_logged_in_visitor_gets_links_only_for_organizations_they_may_look_at(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $otherRegional = Organization::factory()->regional($provincial)->create();
        $local = Organization::factory()->local($regional)->create();
        $otherLocal = Organization::factory()->local($otherRegional)->create();

        $response = $this->actingAs($local)->get('/');

        $response->assertSee('Visiteur :');
        // Own level (anywhere in the tree) and own parent: yes.
        $response->assertSee(route('bottin.organization-members', $local), false);
        $response->assertSee(route('bottin.organization-members', $otherLocal), false);
        $response->assertSee(route('bottin.organization-members', $regional), false);
        // Another region, or the provincial above the parent: no.
        $response->assertDontSee(route('bottin.organization-members', $otherRegional), false);
        $response->assertDontSee(route('bottin.organization-members', $provincial), false);
    }

    public function test_the_members_dialog_lists_usable_roles_only(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $provincial->minimumRoles()->create(['name' => 'Président', 'group' => 'organisation']);

        $president = Member::create(['name' => 'Marie Présidente', 'email' => 'marie@example.com']);
        $former = Member::create(['name' => 'Paul Ancien', 'email' => 'paul@example.com']);
        $regional->memberRoles()->create(['member_id' => $president->id, 'role' => 'Président']);
        $regional->memberRoles()->create(['member_id' => $former->id, 'role' => 'Rôle retiré']);

        $response = $this->actingAs($regional)->get(route('bottin.organization-members', $regional));

        $response->assertOk();
        $response->assertSee('Marie Présidente');
        $response->assertDontSee('Paul Ancien');
    }

    public function test_the_members_dialog_is_refused_for_an_organization_out_of_reach(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $otherRegional = Organization::factory()->regional($provincial)->create();
        $local = Organization::factory()->local($regional)->create();

        $this->actingAs($local)->get(route('bottin.organization-members', $otherRegional))->assertForbidden();
        $this->get(route('bottin.organization-members', $regional))->assertOk();
    }

    public function test_the_members_dialog_requires_being_logged_in(): void
    {
        $provincial = Organization::factory()->provincial()->create();

        $this->get(route('bottin.organization-members', $provincial))->assertRedirect(route('login'));
    }
}
