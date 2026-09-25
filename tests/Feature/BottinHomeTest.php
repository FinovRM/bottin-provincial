<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
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

        $response = $this->actingAs($this->viewerOf($local), 'member')->get('/');

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

        $response = $this->actingAs($this->viewerOf($regional), 'member')->get(route('bottin.organization-members', $regional));

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

        $this->actingAs($this->viewerOf($local), 'member')->get(route('bottin.organization-members', $otherRegional))->assertForbidden();
        $this->get(route('bottin.organization-members', $regional))->assertOk();
    }

    public function test_the_members_dialog_requires_being_logged_in(): void
    {
        $provincial = Organization::factory()->provincial()->create();

        $this->get(route('bottin.organization-members', $provincial))->assertRedirect(route('login'));
    }

    public function test_a_visitor_with_several_roles_starts_at_the_local_level_tied_to_no_organization(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $otherRegional = Organization::factory()->regional($provincial)->create();
        $local = Organization::factory()->local($regional)->create();
        $otherLocal = Organization::factory()->local($otherRegional)->create();

        $member = Member::create(['name' => 'Multi', 'email' => 'multi@example.com']);
        $member->roles()->create(['organization_id' => $provincial->id, 'role' => 'Direction']);
        $member->roles()->create(['organization_id' => $regional->id, 'role' => 'Bénévole']);

        $this->loginAs('multi@example.com');

        $home = $this->get('/');
        $home->assertSee('membre de niveau local (aucun rôle choisi)');
        // Every local organization, but nothing above — not even the parents of their roles.
        $home->assertSee(route('bottin.organization-members', $local), false);
        $home->assertSee(route('bottin.organization-members', $otherLocal), false);
        $home->assertDontSee(route('bottin.organization-members', $regional), false);
        $home->assertDontSee(route('bottin.organization-members', $provincial), false);
    }

    public function test_organisations_and_properties_need_a_chosen_role(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $regional = Organization::factory()->regional($provincial)->create();
        $member = Member::create(['name' => 'Multi', 'email' => 'multi@example.com']);
        $roleAtRegional = $member->roles()->create(['organization_id' => $regional->id, 'role' => 'Bénévole']);
        $member->roles()->create(['organization_id' => $provincial->id, 'role' => 'Direction']);

        $this->loginAs('multi@example.com');

        $home = $this->get('/');
        $home->assertSee('aria-label="Accueil"', false);
        $home->assertDontSee('href="'.route('bottin.organizations').'"', false);
        $home->assertDontSee('href="'.route('profile').'"', false);
        $this->get('/bottin/organisations')->assertRedirect(route('bottin'));
        $this->get('/profil')->assertRedirect(route('bottin'));

        $this->post('/role', ['identity' => "member_role:{$roleAtRegional->id}"]);

        $home = $this->get('/');
        $home->assertSee('href="'.route('bottin.organizations').'"', false);
        $home->assertSee('href="'.route('profile').'"', false);
        $home->assertSee(route('bottin.organization-members', $provincial), false);
        $this->get('/bottin/organisations')->assertOk();
        $this->get('/profil')->assertOk();
    }

    public function test_a_visitor_with_a_single_role_keeps_organisations_and_properties(): void
    {
        $provincial = Organization::factory()->provincial()->create();
        $member = Member::create(['name' => 'Solo', 'email' => 'solo@example.com']);
        $member->roles()->create(['organization_id' => $provincial->id, 'role' => 'Direction']);

        $this->loginAs('solo@example.com');

        $home = $this->get('/');
        $home->assertSee('href="'.route('bottin.organizations').'"', false);
        $home->assertSee('href="'.route('profile').'"', false);
        $home->assertDontSee('id="role-menu"', false);
    }

    private function loginAs(string $email): void
    {
        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => $email]);
        $this->post($url, ['confirmed' => '1']);
    }
}
