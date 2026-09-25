<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use App\Notifications\BottinLoginLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LoginLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_bottin_door_notifies_a_matching_organization(): void
    {
        Notification::fake();

        Organization::factory()->provincial()->create(['responsable_email' => 'responsable@example.com']);

        $response = $this->post('/connexion/bottin', ['email' => 'responsable@example.com']);

        $response->assertRedirect(route('login.sent'));
        Notification::assertSentOnDemand(BottinLoginLinkNotification::class);
    }

    public function test_the_bottin_door_notifies_a_matching_member(): void
    {
        Notification::fake();

        Member::create(['name' => 'Test', 'email' => 'membre@example.com']);

        $response = $this->post('/connexion/bottin', ['email' => 'membre@example.com']);

        $response->assertRedirect(route('login.sent'));
        Notification::assertSentOnDemand(BottinLoginLinkNotification::class);
    }

    public function test_an_unknown_email_shows_an_eligibility_error(): void
    {
        Notification::fake();

        $this->post('/connexion/bottin', ['email' => 'inconnu@example.com'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_the_bottin_declaration_must_be_confirmed(): void
    {
        Member::create(['name' => 'Test', 'email' => 'membre@example.com']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'membre@example.com']);

        $response = $this->post($url, []);

        $response->assertSessionHasErrors('confirmed');
        $this->assertGuest('member');
    }

    public function test_confirming_the_declaration_with_a_single_matching_member_logs_in_and_returns_home(): void
    {
        $member = Member::create(['name' => 'Test', 'email' => 'membre@example.com']);
        $organization = Organization::factory()->provincial()->create();
        $member->roles()->create(['organization_id' => $organization->id, 'role' => 'Bénévole']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'membre@example.com']);

        $response = $this->post($url, ['confirmed' => '1']);

        $response->assertRedirect(route('bottin'));
        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_confirming_the_declaration_with_a_single_matching_organization_logs_in_and_returns_home(): void
    {
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'resp@example.com']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'resp@example.com']);

        $response = $this->post($url, ['confirmed' => '1']);

        $response->assertRedirect(route('bottin'));
        $this->assertAuthenticatedAs($organization);
    }

    public function test_an_unsigned_bottin_verify_link_is_rejected(): void
    {
        Member::create(['name' => 'Test', 'email' => 'membre@example.com']);

        $response = $this->get('/connexion/verifier?email=membre@example.com');

        $response->assertForbidden();
    }

    public function test_multiple_roles_log_in_as_a_plain_member_then_a_role_can_be_chosen_from_the_menu(): void
    {
        $member = Member::create(['name' => 'Test', 'email' => 'membre@example.com']);
        $provincial = Organization::factory()->provincial()->create(['name' => 'Provincial X']);
        $regional = Organization::factory()->regional($provincial)->create(['name' => 'Régional Y']);
        $roleAtProvincial = $member->roles()->create(['organization_id' => $provincial->id, 'role' => 'Direction']);
        $member->roles()->create(['organization_id' => $regional->id, 'role' => 'Bénévole']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'membre@example.com']);

        $this->post($url, ['confirmed' => '1'])->assertRedirect(route('bottin'));
        $this->assertAuthenticatedAs($member, 'member');
        $this->assertNull(session('active_member_role_id'));

        $home = $this->get('/');
        $home->assertSee('membre (aucun rôle choisi)');
        $home->assertSee('Direction — Provincial X');
        $home->assertSee('Bénévole — Régional Y');

        $this->post('/role', ['identity' => "member_role:{$roleAtProvincial->id}"])->assertRedirect(route('bottin'));
        $this->assertAuthenticatedAs($member, 'member');
        $this->assertSame($roleAtProvincial->id, session('active_member_role_id'));

        $this->post('/role', ['identity' => 'member'])->assertRedirect(route('bottin'));
        $this->assertNull(session('active_member_role_id'));
    }

    public function test_a_member_who_is_also_a_responsable_can_switch_to_the_responsable_role(): void
    {
        $organization = Organization::factory()->provincial()->create(['name' => 'Org A', 'responsable_email' => 'multi@example.com']);
        $member = Member::create(['name' => 'Test', 'email' => 'multi@example.com']);
        $member->roles()->create(['organization_id' => $organization->id, 'role' => 'Bénévole']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'multi@example.com']);

        $this->post($url, ['confirmed' => '1'])->assertRedirect(route('bottin'));
        $this->assertAuthenticatedAs($member, 'member');

        $this->post('/role', ['identity' => "organization:{$organization->id}"])->assertRedirect(route('bottin'));
        $this->assertAuthenticatedAs($organization, 'web');
        $this->assertGuest('member');

        $this->post('/role', ['identity' => 'member'])->assertRedirect(route('bottin'));
        $this->assertAuthenticatedAs($member, 'member');
        $this->assertGuest('web');
    }

    public function test_a_responsable_of_several_organizations_logs_in_with_the_most_senior_one(): void
    {
        $provincial = Organization::factory()->provincial()->create(['name' => 'Org A', 'responsable_email' => 'multi@example.com']);
        $regional = Organization::factory()->regional($provincial)->create(['name' => 'Org B', 'responsable_email' => 'multi@example.com']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'multi@example.com']);

        $this->post($url, ['confirmed' => '1'])->assertRedirect(route('bottin'));
        $this->assertAuthenticatedAs($provincial);

        $this->post('/role', ['identity' => "organization:{$regional->id}"])->assertRedirect(route('bottin'));
        $this->assertAuthenticatedAs($regional);
    }

    public function test_a_visitor_cannot_switch_to_a_role_that_is_not_theirs(): void
    {
        $member = Member::create(['name' => 'Test', 'email' => 'membre@example.com']);
        $organization = Organization::factory()->provincial()->create();
        $member->roles()->create(['organization_id' => $organization->id, 'role' => 'Bénévole']);
        $other = Organization::factory()->provincial()->create(['responsable_email' => 'autre@example.com']);

        $this->actingAs($member, 'member')->post('/role', ['identity' => "organization:{$other->id}"])->assertNotFound();
        $this->assertGuest('web');
    }
}
