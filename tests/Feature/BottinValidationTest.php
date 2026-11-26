<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Setting;
use App\Notifications\BottinValidationReminderNotification;
use App\Support\BottinValidation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BottinValidationTest extends TestCase
{
    use RefreshDatabase;

    private function configure(int $day = 1, int $month = 6, int $delay = 10): void
    {
        Setting::put('validation_control_day', $day);
        Setting::put('validation_control_month', $month);
        Setting::put('validation_delay_days', $delay);
    }

    private function travelToDay(string $date): void
    {
        $this->travelTo(Carbon::parse("$date 12:00", BottinValidation::TIMEZONE));
    }

    public function test_an_admin_sets_the_control_date_and_delay(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->get('/admin/proprietes')->assertOk()->assertSee('Validation du bottin');

        $this->actingAs($admin, 'admin')->put(route('admin.properties.validation'), [
            'control_day' => 1, 'control_month' => 6, 'delay_days' => 10,
        ])->assertRedirect(route('admin.properties'));

        $this->assertSame(1, BottinValidation::controlDay());
        $this->assertSame(6, BottinValidation::controlMonth());
        $this->assertSame(10, BottinValidation::delayDays());
    }

    public function test_a_control_date_must_exist_every_year(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->put(route('admin.properties.validation'), [
            'control_day' => 29, 'control_month' => 2, 'delay_days' => 10,
        ])->assertSessionHasErrors('control_day');

        $this->assertFalse(BottinValidation::isConfigured());
    }

    public function test_validations_on_time_or_after_count_and_earlier_ones_do_not(): void
    {
        $this->configure();
        $organization = Organization::factory()->provincial()->create();

        $this->travelToDay('2026-06-05');

        foreach (['2026-05-21' => false, '2026-05-22' => true, '2026-06-01' => true, '2026-06-03' => true] as $validatedOn => $expected) {
            $organization->validated_at = Carbon::parse("$validatedOn 23:00", BottinValidation::TIMEZONE);
            $this->assertSame($expected, BottinValidation::isValid($organization), $validatedOn);
        }

        $organization->validated_at = null;
        $this->assertFalse(BottinValidation::isValid($organization));
    }

    public function test_reminders_go_out_the_day_after_then_daily_from_the_seventh_day(): void
    {
        $this->configure();

        $expected = ['2026-06-01' => true, '2026-06-02' => true, '2026-06-03' => false, '2026-06-07' => false, '2026-06-08' => true, '2026-06-20' => true];

        foreach ($expected as $day => $due) {
            $this->assertSame($due, BottinValidation::reminderControlDate(Carbon::parse($day, BottinValidation::TIMEZONE)) !== null, $day);
        }
    }

    public function test_the_reminder_command_notifies_only_late_responsables_once_each(): void
    {
        Notification::fake();
        $this->configure();
        $this->travelToDay('2026-06-02');

        $late = Organization::factory()->provincial()->withResponsable(['email' => 'late@example.com'])->create(['name' => 'Retard']);
        Organization::factory()->regional($late)->withResponsable(['email' => 'late@example.com'])->create(['name' => 'Retard 2']);
        $onTime = Organization::factory()->regional($late)->withResponsable(['email' => 'ok@example.com'])->create(['name' => 'À temps']);
        $onTime->forceFill(['validated_at' => Carbon::parse('2026-05-25', BottinValidation::TIMEZONE)])->save();

        $this->artisan('bottin:validation-reminders')->assertSuccessful();

        Notification::assertSentOnDemandTimes(BottinValidationReminderNotification::class, 1);
        Notification::assertSentOnDemand(BottinValidationReminderNotification::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'late@example.com'
            && $notification->organizations === ['Retard', 'Retard 2']);
    }

    public function test_no_reminders_without_a_control_date_or_between_reminder_days(): void
    {
        Notification::fake();
        Organization::factory()->provincial()->withResponsable(['email' => 'late@example.com'])->create();

        $this->artisan('bottin:validation-reminders')->assertSuccessful();

        $this->configure();
        $this->travelToDay('2026-06-04');
        $this->artisan('bottin:validation-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_a_responsable_validates_only_with_the_checkbox_and_button(): void
    {
        $this->configure();
        $organization = Organization::factory()->provincial()->withResponsable(['email' => 'r@example.com', 'name' => 'Rita'])->create();

        $this->actingAs($organization)->get('/proprietes')->assertOk()->assertSee('Valider mon bottin')->assertSee('jamais été validé');

        $this->actingAs($organization)->post(route('dashboard.validate'))->assertSessionHasErrors('confirmed');
        $this->assertNull($organization->fresh()->validated_at);

        $this->actingAs($organization)->post(route('dashboard.validate'), ['confirmed' => '1'])->assertRedirect(route('dashboard.properties'));

        $organization->refresh();
        $this->assertNotNull($organization->validated_at);
        $this->assertSame('Rita', $organization->validated_by);
        $this->assertTrue(BottinValidation::isValid($organization));
    }

    public function test_changing_data_does_not_validate(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $organization->update(['name' => 'Nouveau nom']);

        $this->assertNull($organization->fresh()->validated_at);
    }

    public function test_the_home_page_shows_the_last_validation_and_marks_late_organizations(): void
    {
        $this->configure();
        $this->travelToDay('2026-06-05');

        $provincial = Organization::factory()->provincial()->create(['name' => 'Provinciale']);
        $provincial->forceFill(['validated_at' => Carbon::parse('2026-05-28 12:00', BottinValidation::TIMEZONE)])->save();
        Organization::factory()->regional($provincial)->create(['name' => 'Régionale']);

        $member = Member::create(['name' => 'M', 'email' => 'm@example.com']);
        $member->roles()->create(['organization_id' => $provincial->id, 'role' => 'Président']);

        $this->actingAs($member, 'member')->get('/')->assertOk()
            ->assertSee('(28 mai 2026)')
            ->assertSee('(jamais validé — non validé)', false);
    }

    public function test_the_check_starts_at_the_first_control_date_after_its_configuration(): void
    {
        $admin = Admin::factory()->create();
        $organization = Organization::factory()->provincial()->create();
        $this->travelToDay('2026-11-26');

        $this->actingAs($admin, 'admin')->put(route('admin.properties.validation'), [
            'control_day' => 1, 'control_month' => 6, 'delay_days' => 10,
        ]);

        $this->assertTrue(BottinValidation::isValid($organization));
        $this->assertNull(BottinValidation::reminderControlDate());

        $this->travelToDay('2027-06-02');
        $this->assertFalse(BottinValidation::isValid($organization));
        $this->assertNotNull(BottinValidation::reminderControlDate());
    }
}
