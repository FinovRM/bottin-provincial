<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BottinLoginController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.bottin-declaration', [
            'email' => $request->string('email')->toString(),
        ]);
    }

    /**
     * The declaration is confirmed, or a role has been chosen among several —
     * both submit back to this same signed URL, distinguished by which field
     * is present.
     */
    public function store(Request $request): View|RedirectResponse
    {
        $email = $request->string('email')->toString();

        if ($request->filled('role_choice')) {
            $identities = $this->identitiesForEmail($email);
            $chosen = $identities->firstWhere('key', $request->string('role_choice')->toString());

            abort_unless($chosen, 404);

            return $this->login($chosen);
        }

        $request->validate(['confirmed' => ['accepted']]);

        $identities = $this->identitiesForEmail($email);

        if ($identities->isEmpty()) {
            abort(404);
        }

        if ($identities->count() === 1) {
            return $this->login($identities->first());
        }

        return view('auth.bottin-roles', [
            'email' => $email,
            'identities' => $identities,
        ]);
    }

    /**
     * Every identity this courriel grants access to: each organization it is
     * responsable for, and each role a matching member holds.
     *
     * @return Collection<int, array{key: string, organization: string, role: string, guard: string, model: Organization|Member, roleId: ?int}>
     */
    private function identitiesForEmail(string $email): Collection
    {
        $identities = new Collection;

        Organization::where('responsable_email', $email)->get()->each(function (Organization $organization) use ($identities) {
            $identities->push([
                'key' => "organization:{$organization->id}",
                'organization' => $organization->name,
                'role' => 'Responsable de bottin',
                'guard' => 'web',
                'model' => $organization,
                'roleId' => null,
            ]);
        });

        $member = Member::where('email', $email)->first();

        $member?->roles->each(function (MemberRole $role) use ($identities, $member) {
            $identities->push([
                'key' => "member_role:{$role->id}",
                'organization' => $role->organization->name,
                'role' => $role->role,
                'guard' => 'member',
                'model' => $member,
                'roleId' => $role->id,
            ]);
        });

        return $identities;
    }

    /**
     * @param  array{key: string, organization: string, role: string, guard: string, model: Organization|Member, roleId: ?int}  $identity
     */
    private function login(array $identity): RedirectResponse
    {
        if ($identity['guard'] === 'web') {
            Auth::guard('web')->login($identity['model']);
        } else {
            Auth::guard('member')->login($identity['model']);
            session(['active_member_role_id' => $identity['roleId']]);
        }

        return redirect()->route('bottin.index');
    }
}
