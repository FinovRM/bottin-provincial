<?php

namespace App\Policies;

use App\Models\Organization;

class OrganizationPolicy
{
    /**
     * Determine whether the authenticated organization can view another organization's dashboard.
     */
    public function view(Organization $authOrganization, Organization $organization): bool
    {
        return $authOrganization->is($organization);
    }

    /**
     * Determine whether the authenticated organization can create a child organization under the given parent.
     */
    public function create(Organization $authOrganization, Organization $parent): bool
    {
        return $authOrganization->is($parent) && $parent->canCreateChildren();
    }

    /**
     * Determine whether the authenticated organization can update its own fiche.
     */
    public function update(Organization $authOrganization, Organization $organization): bool
    {
        return $authOrganization->is($organization);
    }

    /**
     * Determine whether the authenticated organization can remove a child organization it created.
     */
    public function delete(Organization $authOrganization, Organization $organization): bool
    {
        return $organization->parent_id !== null && $authOrganization->is($organization->parent);
    }
}
