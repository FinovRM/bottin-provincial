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
 * and — when they have several — without a chosen role yet.
 */
class VisitorIdentities
{
    /** A visitor with several roles who hasn't picked one: a plain member at the local level, tied to no organization. */
    public const NO_CHOSEN_ROLE = 'member';

    /** A visitor who is only a responsable, of several organizations, and hasn't picked one yet. */
    public const NO_CHOSEN_ORGANIZATION = 'organization';

    /**
     * @return Collection<int, array{key: string, email: string, organization: ?string, role: string, guard: string, model: Organization|Member, roleId: ?int}>
     */
    public static function forEmail(string $email): Collection
    {
        $identities = new Collection;

        Organization::whereHas('responsables', fn ($responsables) => $responsables->where('email', $email))->orderBy('name')->get()->each(function (Organization $organization) use ($identities, $email) {
            $identities->push([
                'key' => "organization:{$organization->id}",
                'email' => $email,
                'organization' => $organization->name,
                'role' => 'Responsable de bottin',
                'guard' => 'web',
                'model' => $organization,
                'roleId' => null,
            ]);
        });

        $member = Member::where('email', $email)->first();

        $member?->roles->sortBy('organization.name')->each(function (MemberRole $role) use ($identities, $member, $email) {
            $identities->push([
                'key' => "member_role:{$role->id}",
                'email' => $email,
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
     * What the role menu offers: every identity, plus — when there are several —
     * "Aucun rôle choisi" first for a member, or "Choisir une organisation"
     * first for someone who is only a responsable.
     *
     * @return Collection<int, array{key: string, email: string, organization: ?string, role: string, guard: string, model: Organization|Member, roleId: ?int}>
     */
    public static function choicesForEmail(string $email): Collection
    {
        $identities = static::forEmail($email);
        $member = $identities->firstWhere('guard', 'member')['model'] ?? null;

        if ($identities->count() <= 1) {
            return $identities;
        }

        if ($member) {
            $identities->prepend([
                'key' => self::NO_CHOSEN_ROLE,
                'email' => $email,
                'organization' => null,
                'role' => 'Aucun rôle choisi (niveau local)',
                'guard' => 'member',
                'model' => $member,
                'roleId' => null,
            ]);
        } else {
            $identities->prepend([
                'key' => self::NO_CHOSEN_ORGANIZATION,
                'email' => $email,
                'organization' => null,
                'role' => 'Choisir une organisation',
                'guard' => 'web',
                'model' => $identities->sortBy(fn (array $identity) => $identity['model']->level->rank())->first()['model'],
                'roleId' => null,
            ]);
        }

        return $identities;
    }

    /**
     * The identity to log in with once the declaration is confirmed: the only
     * one if there's just one; otherwise no chosen role yet (see choicesForEmail()).
     *
     * @return ?array{key: string, email: string, organization: ?string, role: string, guard: string, model: Organization|Member, roleId: ?int}
     */
    public static function defaultForEmail(string $email): ?array
    {
        return static::choicesForEmail($email)->first();
    }

    /**
     * Whether the visitor has several roles and hasn't picked one yet.
     */
    public static function hasNoChosenRole(): bool
    {
        return (Auth::guard('member')->check() || Auth::guard('web')->check())
            && session('without_chosen_role') === true;
    }

    /**
     * Log in as this identity, leaving whichever other guard was in use.
     *
     * @param  array{key: string, email: string, organization: ?string, role: string, guard: string, model: Organization|Member, roleId: ?int}  $identity
     */
    public static function login(array $identity): void
    {
        if ($identity['guard'] === 'web') {
            Auth::guard('member')->logout();
            session()->forget('active_member_role_id');
            session([
                'without_chosen_role' => $identity['key'] === self::NO_CHOSEN_ORGANIZATION,
                // Which of the organization's responsables is using it.
                'responsable_email' => $identity['email'],
            ]);
            Auth::guard('web')->login($identity['model']);

            return;
        }

        Auth::guard('web')->logout();
        Auth::guard('member')->login($identity['model']);
        session([
            'active_member_role_id' => $identity['roleId'],
            'without_chosen_role' => $identity['key'] === self::NO_CHOSEN_ROLE,
        ]);
    }

    /**
     * The courriel of whoever is logged in on the bottin, as a member or a responsable.
     */
    public static function currentEmail(): ?string
    {
        if (Auth::guard('member')->check()) {
            return Auth::guard('member')->user()->email;
        }

        return session('responsable_email') ?? Auth::guard('web')->user()?->currentResponsable()?->email;
    }

    /**
     * The key of the identity currently in use.
     */
    public static function currentKey(): ?string
    {
        if (Auth::guard('member')->check()) {
            $member = Auth::guard('member')->user();

            if ($member->hasNoChosenRole()) {
                return self::NO_CHOSEN_ROLE;
            }

            $roleId = session('active_member_role_id') ?? $member->roles->first()?->id;

            return $roleId ? "member_role:{$roleId}" : null;
        }

        $organization = Auth::guard('web')->user();

        if (! $organization) {
            return null;
        }

        return static::hasNoChosenRole() ? self::NO_CHOSEN_ORGANIZATION : "organization:{$organization->id}";
    }
}
