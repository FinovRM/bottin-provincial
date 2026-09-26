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

        $response = $this->actingAs($organization)->get('/proprietes');

        $response->assertOk();
        $response->assertSee('Responsable(s) du bottin');
        $response->assertSee('Jeanne Tremblay');
        $response->assertSee('jeanne@example.com');
        $response->assertSee('(514) 555-1234');
        $response->assertSee('Modifier');
    }

    public function test_visiting_modifier_shows_the_responsable_declaration_first(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($organization)->get('/proprietes/responsable/modifier');

        $response->assertOk();
        $response->assertSee('Déclaration du responsable');
        $response->assertDontSee('Adresse courriel');
    }

    public function test_confirming_the_declaration_shows_the_edit_form(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($organization)->post('/proprietes/responsable/modifier', [
            'responsable_confirmed' => '1',
        ]);

        $response->assertOk();
        $response->assertSee('Modifier mes coordonnées');
    }

    public function test_all_fields_are_required_to_proceed(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($organization)->post('/proprietes/responsable/modifier', []);

        $response->assertSessionHasErrors(['responsable_name', 'responsable_email', 'responsable_cell_phone']);
    }

    public function test_the_email_confirmation_must_match(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $response = $this->actingAs($organization)->post('/proprietes/responsable/modifier', [
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

        $response = $this->actingAs($organization)->post('/proprietes/responsable/modifier', [
            'responsable_name' => 'Nouveau Responsable',
            'responsable_email' => 'nouveau@example.com',
            'responsable_email_confirmation' => 'nouveau@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertOk();
        $response->assertSee('Valider la modification');
        $response->assertSee('Vos coordonnées seront mises à jour');
        $this->assertDatabaseHas('responsables', ['organization_id' => $organization->id, 'email' => 'ancien@example.com']);
    }

    public function test_authorizing_the_change_saves_the_responsables_own_coordinates(): void
    {
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'ancien@example.com']);

        $response = $this->actingAs($organization)->post('/proprietes/responsable/modifier', [
            'confirmed_change' => '1',
            'responsable_name' => 'Nouveau Nom',
            'responsable_email' => 'nouveau@example.com',
            'responsable_email_confirmation' => 'nouveau@example.com',
            'responsable_cell_phone' => '514-555-1234',
            'responsable_extension' => '22',
        ]);

        $response->assertRedirect(route('dashboard.properties'));
        $this->assertDatabaseHas('responsables', [
            'organization_id' => $organization->id,
            'name' => 'Nouveau Nom',
            'email' => 'nouveau@example.com',
            'cell_phone' => '5145551234',
            'extension' => '22',
        ]);
        $this->assertDatabaseMissing('responsables', ['email' => 'ancien@example.com']);
    }

    public function test_the_email_cannot_already_be_used_by_another_responsable(): void
    {
        Organization::factory()->provincial()->create(['responsable_email' => 'deja-pris@example.com']);
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'moi@example.com']);

        $response = $this->actingAs($organization)->post('/proprietes/responsable/modifier', [
            'responsable_name' => 'Nouveau Responsable',
            'responsable_email' => 'deja-pris@example.com',
            'responsable_email_confirmation' => 'deja-pris@example.com',
            'responsable_cell_phone' => '514-555-1234',
        ]);

        $response->assertSessionHasErrors('responsable_email');
    }

    public function test_a_responsable_can_add_another_responsable(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $this->actingAs($organization)->post('/proprietes/responsables/ajouter', ['responsable_confirmed' => '1'])
            ->assertOk()
            ->assertSee('Ajouter un responsable du bottin');

        $this->actingAs($organization)->post('/proprietes/responsables', [
            'name' => 'Deuxième Responsable',
            'email' => 'deux@example.com',
            'email_confirmation' => 'deux@example.com',
            'cell_phone' => '819-555-0000',
            'extension' => '12',
        ])->assertRedirect(route('dashboard.properties'));

        $this->assertDatabaseHas('responsables', [
            'organization_id' => $organization->id,
            'email' => 'deux@example.com',
            'cell_phone' => '8195550000',
            'extension' => '12',
        ]);
        $this->assertSame(2, $organization->responsables()->count());
    }

    public function test_a_responsable_cannot_be_added_twice_to_the_same_organization(): void
    {
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'moi@example.com']);

        $this->actingAs($organization)->post('/proprietes/responsables', [
            'name' => 'Moi encore',
            'email' => 'moi@example.com',
            'email_confirmation' => 'moi@example.com',
        ])->assertSessionHasErrors('email');
    }

    public function test_a_responsable_can_remove_another_responsable_but_not_themselves(): void
    {
        $organization = Organization::factory()->provincial()->create(['responsable_email' => 'moi@example.com']);
        $me = $organization->responsables()->first();
        $other = $organization->responsables()->create(['name' => 'Autre', 'email' => 'autre@example.com']);

        $this->withSession(['responsable_email' => 'moi@example.com'])->actingAs($organization);

        $page = $this->get('/proprietes');
        $page->assertSee(route('responsables.destroy', $other), false);
        $page->assertDontSee(route('responsables.destroy', $me), false);

        $this->delete("/proprietes/responsables/{$me->id}")->assertForbidden();
        $this->assertModelExists($me);

        $this->delete("/proprietes/responsables/{$other->id}")->assertRedirect(route('dashboard.properties'));
        $this->assertModelMissing($other);
    }

    public function test_a_responsable_cannot_remove_a_responsable_of_another_organization(): void
    {
        $organization = Organization::factory()->provincial()->create();
        $stranger = Organization::factory()->provincial()->create()->responsables()->first();

        $this->actingAs($organization)->delete("/proprietes/responsables/{$stranger->id}")->assertForbidden();
        $this->assertModelExists($stranger);
    }

    public function test_the_responsable_role_is_reserved_to_the_responsables_section(): void
    {
        $organization = Organization::factory()->provincial()->create();

        $this->actingAs($organization)->post('/membres', [
            'role' => 'responsable du bottin',
            'name' => 'Jeanne Tremblay',
            'email' => 'jeanne@example.com',
            'email_confirmation' => 'jeanne@example.com',
        ])->assertSessionHasErrors('role');

        $this->actingAs($organization)->post('/profil/roles-permis', [
            'group' => 'organisation',
            'name' => 'Responsable du bottin',
        ])->assertSessionHasErrorsIn('allowed-role-add-organisation', 'name');

        $this->actingAs($organization)->post('/profil/roles-minimum', [
            'group' => 'organisation',
            'name' => 'Responsable du bottin',
        ])->assertSessionHasErrorsIn('minimum-role-add-organisation', 'name');

        $this->assertDatabaseMissing('member_roles', ['role' => 'responsable du bottin']);
        $this->assertDatabaseMissing('allowed_roles', ['name' => 'Responsable du bottin']);
        $this->assertDatabaseMissing('minimum_roles', ['name' => 'Responsable du bottin']);
    }
}
