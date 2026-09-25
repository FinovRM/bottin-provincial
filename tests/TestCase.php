<?php

namespace Tests;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var array<int, Member> */
    private array $viewers = [];

    /**
     * A member holding a single role in this organization — who sees the bottin
     * with this organization's scope (responsables no longer use the bottin).
     */
    protected function viewerOf(Organization $organization): Member
    {
        return $this->viewers[$organization->id] ??= tap(
            Member::create(['name' => 'Visiteur Test', 'email' => "visiteur-{$organization->id}@example.com"]),
            fn (Member $member) => $organization->memberRoles()->create(['member_id' => $member->id, 'role' => 'Rôle de test'])
        );
    }
}
