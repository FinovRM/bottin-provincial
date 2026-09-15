<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\Organization;

class MemberPolicy
{
    /**
     * Determine whether the organization can add a member to the given organization.
     */
    public function create(Organization $authOrganization, Organization $organization): bool
    {
        return $authOrganization->is($organization);
    }

    /**
     * Determine whether the organization can update the given member.
     */
    public function update(Organization $authOrganization, Member $member): bool
    {
        return $authOrganization->is($member->organization);
    }

    /**
     * Determine whether the organization can delete the given member.
     */
    public function delete(Organization $authOrganization, Member $member): bool
    {
        return $authOrganization->is($member->organization);
    }
}
