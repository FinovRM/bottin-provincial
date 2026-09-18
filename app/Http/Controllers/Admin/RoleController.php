<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationLevel;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::orderBy('name')->get();

        $titles = [
            OrganizationLevel::Provincial->value => 'Rôles provinciaux',
            OrganizationLevel::Regional->value => 'Rôles régionaux',
            OrganizationLevel::Local->value => 'Rôles locaux',
        ];

        $sections = collect(OrganizationLevel::cases())->map(fn (OrganizationLevel $level) => [
            'level' => $level,
            'title' => $titles[$level->value],
            'roles' => $roles->where('level', $level)->values(),
        ]);

        return view('admin.roles.index', [
            'sections' => $sections,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level' => ['required', 'string', 'in:'.implode(',', array_column(OrganizationLevel::cases(), 'value'))],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles')->where(fn ($query) => $query->where('level', $request->input('level'))),
            ],
        ]);

        Role::create($validated);

        return redirect()->route('admin.roles.index')->with('status', 'Rôle créé.');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles')->where(fn ($query) => $query->where('level', $role->level->value))->ignore($role),
            ],
        ]);

        $role->update($validated);

        return redirect()->route('admin.roles.index')->with('status', 'Rôle mis à jour.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Rôle supprimé.');
    }
}
