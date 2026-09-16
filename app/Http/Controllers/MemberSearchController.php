<?php

namespace App\Http\Controllers;

use App\Models\MemberRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();

        $organizationIds = Auth::user()->visibleOrganizations()->pluck('id');

        $memberRoles = MemberRole::whereIn('organization_id', $organizationIds)
            ->with(['member', 'organization'])
            ->when($query !== '', fn ($memberRoles) => $memberRoles->where(function ($memberRoles) use ($query) {
                $memberRoles->where('role', 'like', "%{$query}%")
                    ->orWhereHas('member', fn ($members) => $members->where('name', 'like', "%{$query}%"));
            }))
            ->get()
            ->sortBy('member.name');

        return view('dashboard.members', [
            'memberRoles' => $memberRoles,
            'query' => $query,
        ]);
    }
}
