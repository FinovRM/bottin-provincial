<?php

namespace App\Support;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ViewerScope
{
    /**
     * Every organization the current viewer (responsable or member) can see —
     * everything at their level or below, anywhere in the tree and in either
     * group — and the organization(s) immediately above them ("ma direction").
     *
     * @return array{0: Collection<int, Organization>, 1: Collection<int, Organization>}
     */
    public static function resolve(): array
    {
        if (Auth::guard('member')->check()) {
            /** @var Member $authMember */
            $authMember = Auth::guard('member')->user();

            return [$authMember->visibleOrganizations(), $authMember->directionOrganizations()];
        }

        /** @var Organization $authOrganization */
        $authOrganization = Auth::guard('web')->user();

        $highest = $authOrganization->highestManagedOrganization();

        return [$highest->visibleToMembers(), $highest->directionOrganizations()];
    }

    /**
     * The organization of the role the viewer is using — none while no role is chosen.
     */
    public static function ownOrganization(): ?Organization
    {
        if (VisitorIdentities::hasNoChosenRole()) {
            return null;
        }

        if (Auth::guard('member')->check()) {
            /** @var Member $authMember */
            $authMember = Auth::guard('member')->user();

            return $authMember->primaryRole()?->organization;
        }

        return Auth::guard('web')->user();
    }

    /**
     * Every organization whose members the current viewer may look at: the
     * usual scope plus the organization(s) immediately above them.
     *
     * @return array<int, int>
     */
    public static function viewableOrganizationIds(): array
    {
        [$scopedOrganizations, $directionOrganizations] = static::resolve();

        return $scopedOrganizations->pluck('id')->merge($directionOrganizations->pluck('id'))->unique()->values()->all();
    }

    /**
     * Whoever is currently logged in, as a responsable or a member — the owner
     * of things like personal filters.
     */
    public static function principal(): Organization|Member
    {
        if (Auth::guard('member')->check()) {
            /** @var Member $authMember */
            return Auth::guard('member')->user();
        }

        /** @var Organization $authOrganization */
        return Auth::guard('web')->user();
    }

    /**
     * Who is looking at the bottin right now — for the black banner under the menu.
     *
     * @return array{name: string, role: string, organization: ?string, responsable: string}
     */
    public static function identity(): array
    {
        if (Auth::guard('member')->check()) {
            /** @var Member $authMember */
            $authMember = Auth::guard('member')->user();

            // Several roles and none chosen yet: a plain local-level member, not tied to one organization.
            if ($authMember->hasNoChosenRole()) {
                return [
                    'name' => $authMember->name,
                    'role' => 'membre de niveau local (aucun rôle choisi)',
                    'organization' => null,
                    'responsable' => '—',
                ];
            }

            $primaryRole = $authMember->primaryRole();

            return [
                'name' => $authMember->name,
                'role' => $primaryRole?->role ?? '—',
                'organization' => $primaryRole?->organization->name ?? '—',
                'responsable' => $primaryRole?->organization->responsablesSummary() ?? '—',
            ];
        }

        /** @var Organization $authOrganization */
        $authOrganization = Auth::guard('web')->user();

        // Only a responsable, of several organizations, none picked yet.
        if (VisitorIdentities::hasNoChosenRole()) {
            return [
                'name' => $authOrganization->currentResponsable()?->name ?? '—',
                'role' => 'responsable de bottin (aucune organisation choisie)',
                'organization' => null,
                'responsable' => '—',
            ];
        }

        return $authOrganization->identity();
    }
}
