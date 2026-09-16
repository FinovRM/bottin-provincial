<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();

        /** @var Member $authMember */
        $authMember = Auth::guard('member')->user();

        $organizationIds = $authMember->visibleOrganizations()->pluck('id');

        $memberRoles = MemberRole::whereIn('organization_id', $organizationIds)
            ->with(['member', 'organization'])
            ->when($query !== '', fn ($memberRoles) => $memberRoles->where(function ($memberRoles) use ($query) {
                $memberRoles->where('role', 'like', "%{$query}%")
                    ->orWhereHas('member', fn ($members) => $members->where('name', 'like', "%{$query}%"));
            }))
            ->get()
            ->sortBy('member.name');

        return view('member.dashboard', [
            'memberRoles' => $memberRoles,
            'query' => $query,
        ]);
    }
}
