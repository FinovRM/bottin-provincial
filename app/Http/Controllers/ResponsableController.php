<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Support\CellPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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

        if ($request->boolean('confirmed_change')) {
            $organization->update($request->only([
                'responsable_name', 'responsable_email', 'responsable_cell_phone',
            ]));

            return redirect()->route('dashboard.properties');
        }

        if ($request->has('responsable_confirmed')) {
            $request->validate(['responsable_confirmed' => ['accepted']]);

            return view('dashboard.responsable-edit', ['organization' => $organization]);
        }

        $validated = $request->validate([
            'responsable_name' => ['required', 'string', 'max:255'],
            'responsable_email' => ['required', 'email', 'max:255', 'confirmed', 'unique:organizations,responsable_email,'.$organization->id],
            'responsable_cell_phone' => ['required', 'string', 'max:255'],
        ]);

        $validated['responsable_cell_phone'] = CellPhone::normalize($validated['responsable_cell_phone']);

        return view('dashboard.responsable-confirm', [
            'pending' => $validated,
        ]);
    }
}
