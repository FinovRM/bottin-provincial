<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Responsable;
use App\Support\CellPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The responsables of the organization in use: each one edits their own
 * coordinates, and adds or removes the others — never themselves.
 */
class ResponsableController extends Controller
{
    public function edit(): View
    {
        return view('auth.responsable-declaration');
    }

    /**
     * Three steps submit to this same URI: the responsable declaration is
     * confirmed, the edit form's fields are confirmed, or the pending change
     * is finally authorized — distinguished by which marker is present.
     */
    public function update(Request $request): View|RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();
        $responsable = $organization->currentResponsable();

        if ($request->has('responsable_confirmed')) {
            $request->validate(['responsable_confirmed' => ['accepted']]);

            return view('dashboard.responsable-edit', ['responsable' => $responsable]);
        }

        $validated = $request->validate([
            'responsable_name' => ['required', 'string', 'max:255'],
            // Another person's courriel would merge two responsables into one.
            'responsable_email' => ['required', 'email', 'max:255', 'confirmed', Rule::unique('responsables', 'email')
                ->where(fn ($responsables) => $responsables->where('email', '!=', $responsable->email))],
            'responsable_cell_phone' => ['required', 'string', 'max:255'],
            'responsable_extension' => ['nullable', 'string', 'max:10'],
        ], [
            'responsable_email.unique' => 'Ce courriel est déjà celui d\'un autre responsable.',
        ]);

        if ($request->boolean('confirmed_change')) {
            $responsable->update([
                'name' => $validated['responsable_name'],
                'email' => $validated['responsable_email'],
                'cell_phone' => $validated['responsable_cell_phone'],
                'extension' => $validated['responsable_extension'] ?? null,
            ]);

            // Still the same person, under their new courriel.
            session(['responsable_email' => $responsable->email]);

            return redirect()->route('dashboard.properties');
        }

        $validated['responsable_cell_phone'] = CellPhone::normalize($validated['responsable_cell_phone']);
        $validated['responsable_extension'] = CellPhone::normalize($validated['responsable_extension'] ?? null);

        return view('dashboard.responsable-confirm', [
            'pending' => $validated,
        ]);
    }

    public function create(): View
    {
        return view('auth.responsable-declaration');
    }

    public function createConfirmed(Request $request): View
    {
        $request->validate(['responsable_confirmed' => ['accepted']]);

        return view('dashboard.responsables-create');
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'confirmed', Rule::unique('responsables')->where('organization_id', $organization->id)],
            'cell_phone' => ['nullable', 'string', 'max:255'],
            'extension' => ['nullable', 'string', 'max:10'],
        ], [
            'email.unique' => 'Cette personne est déjà responsable de cette organisation.',
        ]);

        $organization->responsables()->create($validated);
        $organization->touch();

        return redirect()->route('dashboard.properties');
    }

    public function destroy(Responsable $responsable): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        abort_unless($responsable->organization->is($organization), 403);
        // Removing oneself would leave the organization without the person using it.
        abort_if($responsable->is($organization->currentResponsable()), 403);

        $responsable->delete();
        $organization->touch();

        return redirect()->route('dashboard.properties');
    }
}
