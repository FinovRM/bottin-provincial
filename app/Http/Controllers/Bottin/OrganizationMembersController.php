<?php

namespace App\Http\Controllers\Bottin;

use App\Http\Controllers\Controller;
use App\Models\MemberRole;
use App\Models\Organization;
use App\Support\ViewerScope;
use Illuminate\View\View;

class OrganizationMembersController extends Controller
{
    /**
     * The members of one organization, for the dialog opened from the home
     * page — only for an organization the viewer may look at, and only members
     * holding a role currently usable there.
     */
    public function __invoke(Organization $organization): View
    {
        abort_unless(in_array($organization->id, ViewerScope::viewableOrganizationIds(), true), 403);

        $usableRoleNames = $organization->usableRoleNames();

        $memberRoles = $organization->memberRoles()
            ->with('member')
            ->get()
            ->filter(fn (MemberRole $memberRole) => $usableRoleNames === null || in_array($memberRole->role, $usableRoleNames, true))
            ->sortBy([
                ['role', 'asc'],
                ['member.name', 'asc'],
            ]);

        return view('bottin.organization-members', [
            'organization' => $organization,
            'memberRoles' => $memberRoles,
        ]);
    }
}
