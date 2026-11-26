<?php

namespace Tests\Feature;

use App\Models\BottinRequest;
use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use App\Notifications\BottinChangeConfirmationNotification;
use App\Notifications\BottinRemovalRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BottinRequestTest extends TestCase
{
    use RefreshDatabase;

    private Organization $regional;

    private Organization $localA;

    private Organization $localB;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $provincial = Organization::factory()->provincial()->withResponsable(['email' => 'prov@example.com'])->create();
        $this->regional = Organization::factory()->regional($provincial)->withResponsable(['email' => 'reg@example.com'])->create();
        $this->localA = Organization::factory()->local($this->regional)->withResponsable(['email' => 'a@example.com'])->create(['name' => 'AHM A']);
        $this->localB = Organization::factory()->local($this->regional)->withResponsable(['email' => 'b@example.com'])->create(['name' => 'AHM B']);

        $this->member = Member::create(['name' => 'Jean Tremblay', 'email' => 'jean@example.com', 'cell_phone' => '8195551234']);
        $this->member->roles()->create(['organization_id' => $this->localA->id, 'role' => 'Président']);
        $this->member->roles()->create(['organization_id' => $this->localB->id, 'role' => 'Registraire']);
    }

    private function openDeclaration(string $email = 'jean@example.com'): string
    {
        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => $email]);
        $this->get($url)->assertOk();

        return $url;
    }

    private function key(string $role): string
    {
        return 'member_role:'.MemberRole::where('role', $role)->value('id');
    }

    public function test_the_declaration_shows_the_requester_and_both_options(): void
    {
        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'jean@example.com']);

        $this->get($url)->assertOk()
            ->assertSee('Jean Tremblay')
            ->assertSee('(819) 555-1234')
            ->assertSee('Modifier')
            ->assertSee('Retirez-moi du bottin')
            ->assertSee('Président');
    }

    public function test_requests_need_a_declaration_opened_from_a_signed_link(): void
    {
        $this->post(route('bottin-requests.remove'), ['roles' => ['x']])->assertForbidden();
    }

    public function test_a_modification_needs_a_valid_email(): void
    {
        $url = $this->openDeclaration();

        $this->from($url)->post(route('bottin-requests.modify'), [
            'roles' => [$this->key('Président')], 'new_email' => '', 'new_cell_phone' => '8195550000',
        ])->assertSessionHasErrorsIn('modification', 'new_email');

        $this->from($url)->post(route('bottin-requests.modify'), [
            'roles' => [$this->key('Président')], 'new_email' => 'pas-un-courriel', 'new_cell_phone' => '8195550000',
        ])->assertSessionHasErrorsIn('modification', 'new_email');

        $this->assertDatabaseCount('bottin_requests', 0);
    }

    public function test_a_role_not_held_cannot_be_chosen(): void
    {
        $this->openDeclaration();

        $this->post(route('bottin-requests.modify'), [
            'roles' => ['member_role:999'], 'new_email' => 'new@example.com', 'new_cell_phone' => '8195550000',
        ])->assertSessionHasErrorsIn('modification', 'roles.0');
    }

    public function test_a_modification_is_confirmed_from_the_new_email_then_applied(): void
    {
        Notification::fake();
        $this->openDeclaration();

        $this->post(route('bottin-requests.modify'), [
            'roles' => [$this->key('Président')], 'new_email' => 'Nouveau@Example.com', 'new_cell_phone' => '(819) 555-0000',
        ])->assertSessionHas('status');

        $request = BottinRequest::sole();
        $this->assertNull($request->confirmed_at);

        $url = null;
        Notification::assertSentOnDemand(BottinChangeConfirmationNotification::class, function ($notification, $channels, AnonymousNotifiable $notifiable) use (&$url) {
            $url = $notification->toMail($notifiable)->actionUrl;

            return $notifiable->routes['mail'] === 'nouveau@example.com';
        });

        $this->get($url)->assertOk()->assertSee('Modification confirmée');

        $this->assertNotNull($request->fresh()->confirmed_at);
        $new = Member::where('email', 'nouveau@example.com')->sole();
        $this->assertSame('(819) 555-0000', $new->cell_phone);
        $this->assertSame(['Président'], $new->roles->pluck('role')->all());
        $this->assertSame(['Registraire'], $this->member->fresh()->roles->pluck('role')->all());
    }

    public function test_modifying_every_role_changes_the_persons_coordinates(): void
    {
        Notification::fake();
        $this->openDeclaration();

        $this->post(route('bottin-requests.modify'), [
            'roles' => [$this->key('Président'), $this->key('Registraire')], 'new_email' => 'nouveau@example.com', 'new_cell_phone' => '8195550000',
        ]);

        $this->get(URL::temporarySignedRoute('bottin-requests.confirm', now()->addDay(), ['bottinRequest' => BottinRequest::sole()]))->assertOk();

        $this->assertSame('nouveau@example.com', $this->member->fresh()->email);
        $this->assertSame(1, Member::count());
    }

    public function test_an_unsigned_confirmation_link_is_rejected(): void
    {
        $request = BottinRequest::create(['type' => 'modification', 'name' => 'X', 'email' => 'jean@example.com', 'roles' => [], 'new_email' => 'n@example.com']);

        $this->get(route('bottin-requests.confirm', $request))->assertForbidden();
    }

    public function test_a_removal_notifies_each_responsable_with_their_roles_only(): void
    {
        Notification::fake();
        $this->openDeclaration();

        $this->post(route('bottin-requests.remove'), [
            'roles' => [$this->key('Président'), $this->key('Registraire')],
        ])->assertSessionHas('status');

        $this->assertNotNull(BottinRequest::sole()->confirmed_at);

        foreach (['a@example.com' => 'AHM A', 'b@example.com' => 'AHM B'] as $email => $organization) {
            Notification::assertSentOnDemand(BottinRemovalRequestNotification::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $email
                && $notification->email === 'jean@example.com'
                && count($notification->roles) === 1
                && $notification->roles[0]['organization'] === $organization);
        }

        Notification::assertSentOnDemandTimes(BottinRemovalRequestNotification::class, 2);
        $this->assertSame(2, MemberRole::count());
    }

    public function test_responsable_de_bottin_is_not_among_the_roles(): void
    {
        $this->member->update(['email' => 'a@example.com']);

        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'a@example.com']);
        $this->get($url)->assertOk()->assertSee('Président')->assertDontSee('Responsable de bottin');

        $this->post(route('bottin-requests.remove'), ['roles' => ["organization:{$this->localA->id}"]])
            ->assertSessionHasErrorsIn('retrait', 'roles.0');
    }

    public function test_a_responsable_without_member_roles_gets_no_options(): void
    {
        $url = URL::temporarySignedRoute('bottin-login.verify', now()->addMinutes(15), ['email' => 'reg@example.com']);

        $this->get($url)->assertOk()->assertDontSee('Retirez-moi du bottin');
    }
}
