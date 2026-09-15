<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Notifications\OrganizationLoginLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LoginLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_a_link_notifies_the_matching_organization(): void
    {
        Notification::fake();

        $organization = Organization::factory()->provincial()->create([
            'responsable_email' => 'responsable@example.com',
        ]);

        $response = $this->post('/connexion', ['email' => 'responsable@example.com']);

        $response->assertRedirect(route('login.sent'));
        Notification::assertSentTo($organization, OrganizationLoginLinkNotification::class);
    }

    public function test_requesting_a_link_for_an_unknown_email_does_not_error(): void
    {
        Notification::fake();

        $response = $this->post('/connexion', ['email' => 'inconnu@example.com']);

        $response->assertRedirect(route('login.sent'));
        Notification::assertNothingSent();
    }

    public function test_a_signed_link_logs_the_organization_in(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $url = URL::temporarySignedRoute('login.consume', now()->addMinutes(15), ['organization' => $organization->id]);

        $response = $this->get($url);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($organization);
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->get(route('login.consume', ['organization' => $organization->id]));

        $response->assertForbidden();
        $this->assertGuest();
    }
}
