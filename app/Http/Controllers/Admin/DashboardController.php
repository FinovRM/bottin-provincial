<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();

        $organizations = Organization::with('parent')
            ->when($query !== '', fn ($organizations) => $organizations->where(function ($organizations) use ($query) {
                $organizations->where('name', 'like', "%{$query}%")
                    ->orWhere('responsable_email', 'like', "%{$query}%");
            }))
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('admin.dashboard', [
            'organizations' => $organizations,
            'query' => $query,
        ]);
    }
}
