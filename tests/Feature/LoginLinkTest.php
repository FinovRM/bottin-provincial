<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use App\Notifications\BottinLoginLinkNotification;
use App\Notifications\OrganizationLoginLinkNotification;
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

    public function test_the_editeur_door_notifies_a_matching_organization(): void
    {
        Notification::fake();

        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'resp@example.com']);

        $response = $this->post('/connexion/editeur', ['email' => 'resp@example.com']);

        $response->assertRedirect(route('login.sent'));
        Notification::assertSentTo($organization, OrganizationLoginLinkNotification::class);
    }

    public function test_the_editeur_door_ignores_members(): void
    {
        Notification::fake();

        Member::create(['name' => 'Test', 'email' => 'membre@example.com']);

        $response = $this->post('/connexion/editeur', ['email' => 'membre@example.com']);

        $response->assertRedirect(route('login.sent'));
        Notification::assertNothingSent();
    }

    public function test_an_unknown_email_does_not_error_on_either_door(): void
    {
        Notification::fake();

        $this->post('/connexion/bottin', ['email' => 'inconnu@example.com'])
            ->assertRedirect(route('login.sent'));
        $this->post('/connexion/editeur', ['email' => 'inconnu@example.com'])
            ->assertRedirect(route('login.sent'));

        Notification::assertNothingSent();
    }

    public function test_a_signed_editeur_link_goes_to_the_dashboard(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $url = URL::temporarySignedRoute('login.consume', now()->addMinutes(15), [
            'organization' => $organization->id,
        ]);

        $response = $this->get($url);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($organization);
    }

    public function test_an_editeur_in_charge_of_several_organizations_is_sent_to_the_switch_page(): void
    {
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'multi@example.com']);
        Organization::factory()->regional($provincial)->create(['responsable_email' => 'multi@example.com']);

        $url = URL::temporarySignedRoute('login.consume', now()->addMinutes(15), [
            'organization' => $provincial->id,
        ]);

        $response = $this->get($url);

        $response->assertRedirect(route('dashboard.switch'));
    }

    public function test_an_unsigned_editeur_link_is_rejected(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->get(route('login.consume', ['organization' => $organization->id]));

        $response->assertForbidden();
        $this->assertGuest();
    }

    public function test_the_bottin_declaration_must_be_confirmed(): void
    {
        Member::create(['name' => 'Test', 'email' => 'membre@example.com']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'membre@example.com']);

        $response = $this->post($url, []);

        $response->assertSessionHasErrors('confirmed');
        $this->assertGuest('member');
    }

    public function test_confirming_the_declaration_with_a_single_matching_member_logs_in_and_reaches_the_bottin(): void
    {
        $member = Member::create(['name' => 'Test', 'email' => 'membre@example.com']);
        $organization = Organization::factory()->provincial()->create();
        $member->roles()->create(['organization_id' => $organization->id, 'role' => 'Bénévole']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'membre@example.com']);

        $response = $this->post($url, ['confirmed' => '1']);

        $response->assertRedirect(route('bottin.index'));
        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_confirming_the_declaration_with_a_single_matching_organization_logs_in_and_reaches_the_bottin(): void
    {
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'resp@example.com']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'resp@example.com']);

        $response = $this->post($url, ['confirmed' => '1']);

        $response->assertRedirect(route('bottin.index'));
        $this->assertAuthenticatedAs($organization);
    }

    public function test_an_unsigned_bottin_verify_link_is_rejected(): void
    {
        Member::create(['name' => 'Test', 'email' => 'membre@example.com']);

        $response = $this->get('/connexion/verifier?email=membre@example.com');

        $response->assertForbidden();
    }

    public function test_multiple_roles_shows_a_choice_and_scopes_access_to_the_chosen_one(): void
    {
        $member = Member::create(['name' => 'Test', 'email' => 'membre@example.com']);
        $provincial = Organization::factory()->provincial()->create(['name' => 'Provincial X']);
        $regional = Organization::factory()->regional($provincial)->create(['name' => 'Régional Y']);
        $roleAtProvincial = $member->roles()->create(['organization_id' => $provincial->id, 'role' => 'Direction']);
        $member->roles()->create(['organization_id' => $regional->id, 'role' => 'Bénévole']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'membre@example.com']);

        $response = $this->post($url, ['confirmed' => '1']);

        $response->assertOk();
        $response->assertSee('Provincial X');
        $response->assertSee('Régional Y');
        $response->assertSee('Direction');
        $response->assertSee('Bénévole');
        $this->assertGuest('member');

        $choiceResponse = $this->post($url, ['role_choice' => "member_role:{$roleAtProvincial->id}"]);

        $choiceResponse->assertRedirect(route('bottin.index'));
        $this->assertAuthenticatedAs($member, 'member');
        $this->assertSame($roleAtProvincial->id, session('active_member_role_id'));
    }

    public function test_a_responsable_of_several_organizations_gets_a_choice_on_the_bottin_door(): void
    {
        Organization::factory()->provincial()->create(['name' => 'Org A', 'responsable_email' => 'multi@example.com']);
        Organization::factory()->provincial()->create(['name' => 'Org B', 'responsable_email' => 'multi@example.com']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'multi@example.com']);

        $response = $this->post($url, ['confirmed' => '1']);

        $response->assertOk();
        $response->assertSee('Org A');
        $response->assertSee('Org B');
        $response->assertSee('Responsable de bottin');
    }
}
