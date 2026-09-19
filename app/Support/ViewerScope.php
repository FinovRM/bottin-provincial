<?php

namespace App\Support;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ViewerScope
{
    /**
     * Every organization the current viewer (responsable or member) can see,
     * and the organization(s) immediately above them ("ma direction").
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
}
