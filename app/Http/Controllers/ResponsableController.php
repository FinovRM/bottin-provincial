<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResponsableController extends Controller
{
    public function edit(): View
    {
        return view('dashboard.responsable-edit', [
            'organization' => Auth::user(),
        ]);
    }

    /**
     * The edit form's fields are confirmed, or the pending change is finally
     * authorized — both submit here, distinguished by the `confirmed_change`
     * marker only present on the second step.
     */
    public function update(Request $request): View|RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        if ($request->boolean('confirmed_change')) {
            $organization->update($request->only([
                'responsable_first_name', 'responsable_last_name', 'responsable_email', 'responsable_cell_phone',
            ]));

            return redirect()->route('dashboard.properties');
        }

        $validated = $request->validate([
            'responsable_first_name' => ['required', 'string', 'max:255'],
            'responsable_last_name' => ['required', 'string', 'max:255'],
            'responsable_email' => ['required', 'email', 'max:255', 'confirmed', 'unique:organizations,responsable_email,'.$organization->id],
            'responsable_cell_phone' => ['required', 'string', 'max:255'],
        ]);

        return view('dashboard.responsable-confirm', [
            'pending' => $validated,
        ]);
    }
}
