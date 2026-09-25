<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The logged-in admin's own name and password, and the list of admins.
 */
class PropertiesController extends Controller
{
    public function show(): View
    {
        return view('admin.properties', [
            'admin' => Auth::guard('admin')->user(),
            'admins' => Admin::orderBy('name')->get(),
        ]);
    }

    public function updateName(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Auth::guard('admin')->user()->update($validated);

        return redirect()->route('admin.properties')->with('status', 'Nom mis à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
            'password.min' => 'Le mot de passe doit compter au moins 8 caractères.',
        ]);

        Auth::guard('admin')->user()->update(['password' => $validated['password']]);

        return redirect()->route('admin.properties')->with('status', 'Mot de passe modifié.');
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'new_admin_name' => ['required', 'string', 'max:255'],
            'new_admin_email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'new_admin_password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'new_admin_email.unique' => 'Un administrateur utilise déjà ce courriel.',
            'new_admin_email.email' => 'Le courriel n\'est pas valide.',
            'new_admin_password.confirmed' => 'La confirmation ne correspond pas au mot de passe.',
            'new_admin_password.min' => 'Le mot de passe doit compter au moins 8 caractères.',
        ]);

        Admin::create([
            'name' => $validated['new_admin_name'],
            'email' => $validated['new_admin_email'],
            'password' => $validated['new_admin_password'],
        ]);

        return redirect()->route('admin.properties')->with('status', 'Administrateur ajouté.');
    }
}
