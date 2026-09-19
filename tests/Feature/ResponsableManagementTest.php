<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsableManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_properties_page_lists_the_responsable_in_a_table_with_a_modifier_link(): void
    {
        $organization = Organization::factory()->provincial()->create([
            'responsable_name' => 'Jeanne Tremblay',
            'responsable_email' => 'jeanne@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response = $this->actingAs($organization)->post('/tableau-de-bord/proprietes', ['responsable_confirmed' => '1']);

        $response->assertOk();
        $response->assertSee('Responsable du bottin');
        $response->assertSee('Jeanne Tremblay');
        $response->assertSee('jeanne@example.com');
        $response->assertSee('514-555-1234');
        $response->assertSee('Modifier');
    }

    public function test_all_fields_are_required_to_proceed(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($organization)->post('/tableau-de-bord/proprietes/responsable/modifier', []);

        $response->assertSessionHasErrors(['responsable_name', 'responsable_email', 'responsable_cell_phone']);
    }

    public function test_the_email_confirmation_must_match(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($organization)->post('/tableau-de-bord/proprietes/responsable/modifier', [
            'responsable_name' => 'Nouveau Responsable',
            'responsable_email' => 'nouveau@example.com',
            'responsable_email_confirmation' => 'different@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertSessionHasErrors('responsable_email');
    }

    public function test_a_matching_email_confirmation_reaches_the_validation_step_without_saving_yet(): void
    {
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'ancien@example.com']);

        $response = $this->actingAs($organization)->post('/tableau-de-bord/proprietes/responsable/modifier', [
            'responsable_name' => 'Nouveau Responsable',
            'responsable_email' => 'nouveau@example.com',
            'responsable_email_confirmation' => 'nouveau@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertOk();
        $response->assertSee('Valider la modification');
        $response->assertSee('Un nouveau responsable sera dorénavant en vigueur');
        $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'responsable_email' => 'ancien@example.com']);
    }

    public function test_authorizing_the_change_saves_the_new_responsable(): void
    {
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'ancien@example.com']);

        $response = $this->actingAs($organization)->post('/tableau-de-bord/proprietes/responsable/modifier', [
            'confirmed_change' => '1',
            'responsable_name' => 'Nouveau Responsable',
            'responsable_email' => 'nouveau@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'responsable_name' => 'Nouveau Responsable',
            'responsable_email' => 'nouveau@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);
    }

    public function test_the_email_cannot_already_be_used_by_another_organization(): void
    {
        Organization::factory()->provincial()->create(['responsable_email' => 'deja-pris@example.com']);
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'moi@example.com']);

        $response = $this->actingAs($organization)->post('/tableau-de-bord/proprietes/responsable/modifier', [
            'responsable_name' => 'Nouveau Responsable',
            'responsable_email' => 'deja-pris@example.com',
            'responsable_email_confirmation' => 'deja-pris@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertSessionHasErrors('responsable_email');
    }
}
