<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use App\Notifications\MemberLoginLinkNotification;
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

        $organization = Organization::factory()->provincial()->create([
            'responsable_email' => 'responsable@example.com',
        ]);

        $response = $this->post('/connexion/bottin', ['email' => 'responsable@example.com']);

        $response->assertRedirect(route('login.sent'));
        Notification::assertSentTo($organization, OrganizationLoginLinkNotification::class);
    }

    public function test_the_bottin_door_notifies_a_matching_member(): void
    {
        Notification::fake();

        $member = Member::create(['name' => 'Test', 'email' => 'membre@example.com']);

        $response = $this->post('/connexion/bottin', ['email' => 'membre@example.com']);

        $response->assertRedirect(route('login.sent'));
        Notification::assertSentTo($member, MemberLoginLinkNotification::class);
    }

    public function test_the_editeur_door_notifies_a_matching_organization_but_ignores_members(): void
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

    public function test_a_signed_link_with_editeur_intent_goes_to_the_dashboard(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $url = URL::temporarySignedRoute('login.consume', now()->addMinutes(15), [
            'organization' => $organization->id,
            'intent' => 'editeur',
        ]);

        $response = $this->get($url);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($organization);
    }

    public function test_a_signed_link_with_bottin_intent_goes_to_the_bottin(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $url = URL::temporarySignedRoute('login.consume', now()->addMinutes(15), [
            'organization' => $organization->id,
            'intent' => 'bottin',
        ]);

        $response = $this->get($url);

        $response->assertRedirect(route('bottin.index'));
        $this->assertAuthenticatedAs($organization);
    }

    public function test_an_editeur_in_charge_of_several_organizations_is_sent_to_the_switch_page(): void
    {
        $provincial = Organization::factory()->provincial()->create(['responsable_email' => 'multi@example.com']);
        $regional = Organization::factory()->regional($provincial)->create(['responsable_email' => 'multi@example.com']);

        $url = URL::temporarySignedRoute('login.consume', now()->addMinutes(15), [
            'organization' => $provincial->id,
            'intent' => 'editeur',
        ]);

        $response = $this->get($url);

        $response->assertRedirect(route('dashboard.switch'));
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->get(route('login.consume', ['organization' => $organization->id, 'intent' => 'editeur']));

        $response->assertForbidden();
        $this->assertGuest();
    }

    public function test_a_signed_member_link_goes_to_the_bottin(): void
    {
        $member = Member::create(['name' => 'Test', 'email' => 'membre@example.com']);

        $url = URL::temporarySignedRoute('member-login.consume', now()->addMinutes(15), ['member' => $member->id]);

        $response = $this->get($url);

        $response->assertRedirect(route('bottin.index'));
        $this->assertAuthenticatedAs($member, 'member');
    }
}
