<?php

namespace App\Support;

use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Every way a visitor (one courriel) can be logged in: as the responsable of
 * each organization they're in charge of, as the holder of each member role,
 * and — when they have several — as a plain member not tied to one role.
 */
class VisitorIdentities
{
    /** The identity of a member with several roles who hasn't picked one: sees what all their roles allow. */
    public const ALL_MEMBER_ROLES = 'member';

    /**
     * @return Collection<int, array{key: string, organization: ?string, role: string, guard: string, model: Organization|Member, roleId: ?int}>
     */
    public static function forEmail(string $email): Collection
    {
        $identities = new Collection;

        Organization::where('responsable_email', $email)->orderBy('name')->get()->each(function (Organization $organization) use ($identities) {
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

        $member?->roles->sortBy('organization.name')->each(function (MemberRole $role) use ($identities, $member) {
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
     * What the role menu offers: every identity, plus "Membre (tous mes rôles)"
     * first when the visitor is a member and has more than one identity.
     *
     * @return Collection<int, array{key: string, organization: ?string, role: string, guard: string, model: Organization|Member, roleId: ?int}>
     */
    public static function choicesForEmail(string $email): Collection
    {
        $identities = static::forEmail($email);
        $member = $identities->firstWhere('guard', 'member')['model'] ?? null;

        if ($member && $identities->count() > 1) {
            $identities->prepend([
                'key' => self::ALL_MEMBER_ROLES,
                'organization' => null,
                'role' => 'Membre (tous mes rôles)',
                'guard' => 'member',
                'model' => $member,
                'roleId' => null,
            ]);
        }

        return $identities;
    }

    /**
     * The identity to log in with once the declaration is confirmed: the only
     * one if there's just one; otherwise a plain member (all roles) when the
     * visitor is a member, or else the most senior organization they're in
     * charge of.
     *
     * @return ?array{key: string, organization: ?string, role: string, guard: string, model: Organization|Member, roleId: ?int}
     */
    public static function defaultForEmail(string $email): ?array
    {
        $choices = static::choicesForEmail($email);

        return $choices->firstWhere('key', self::ALL_MEMBER_ROLES)
            ?? $choices->sortBy(fn (array $identity) => $identity['model'] instanceof Organization ? $identity['model']->level->rank() : 0)->first();
    }

    /**
     * Log in as this identity, leaving whichever other guard was in use.
     *
     * @param  array{key: string, organization: ?string, role: string, guard: string, model: Organization|Member, roleId: ?int}  $identity
     */
    public static function login(array $identity): void
    {
        if ($identity['guard'] === 'web') {
            Auth::guard('member')->logout();
            session()->forget('active_member_role_id');
            Auth::guard('web')->login($identity['model']);

            return;
        }

        Auth::guard('web')->logout();
        Auth::guard('member')->login($identity['model']);
        session(['active_member_role_id' => $identity['roleId']]);
    }

    /**
     * The courriel of whoever is logged in on the bottin, as a member or a responsable.
     */
    public static function currentEmail(): ?string
    {
        if (Auth::guard('member')->check()) {
            return Auth::guard('member')->user()->email;
        }

        return Auth::guard('web')->user()?->responsable_email;
    }

    /**
     * The key of the identity currently in use.
     */
    public static function currentKey(): ?string
    {
        if (Auth::guard('member')->check()) {
            $roleId = session('active_member_role_id');

            if ($roleId) {
                return "member_role:{$roleId}";
            }

            $roles = Auth::guard('member')->user()->roles;

            return $roles->count() === 1 ? "member_role:{$roles->first()->id}" : self::ALL_MEMBER_ROLES;
        }

        $organization = Auth::guard('web')->user();

        return $organization ? "organization:{$organization->id}" : null;
    }
}
