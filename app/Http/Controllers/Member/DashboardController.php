<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
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

        $members = Member::whereIn('organization_id', $organizationIds)
            ->with('organization')
            ->when($query !== '', fn ($members) => $members->where(function ($members) use ($query) {
                $members->where('name', 'like', "%{$query}%")
                    ->orWhere('role', 'like', "%{$query}%");
            }))
            ->orderBy('name')
            ->get();

        return view('member.dashboard', [
            'members' => $members,
            'query' => $query,
        ]);
    }
}
