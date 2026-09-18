<?php

namespace App\Policies;

use App\Models\MemberRole;
use App\Models\Organization;

class MemberRolePolicy
{
    /**
     * Determine whether the organization can add a role to the given organization.
     */
    public function create(Organization $authOrganization, Organization $organization): bool
    {
        return $authOrganization->is($organization);
    }

    /**
     * Determine whether the organization can delete the given role.
     */
    public function delete(Organization $authOrganization, MemberRole $memberRole): bool
    {
        return $authOrganization->is($memberRole->organization);
    }
}
