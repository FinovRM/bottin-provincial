<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();

        $organizationIds = Auth::user()->visibleOrganizations()->pluck('id');

        $members = Member::whereIn('organization_id', $organizationIds)
            ->with('organization')
            ->when($query !== '', fn ($members) => $members->where(function ($members) use ($query) {
                $members->where('name', 'like', "%{$query}%")
                    ->orWhere('role', 'like', "%{$query}%");
            }))
            ->orderBy('name')
            ->get();

        return view('dashboard.members', [
            'members' => $members,
            'query' => $query,
        ]);
    }
}
