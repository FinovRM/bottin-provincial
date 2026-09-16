@extends('layouts.app')

@section('title', 'Interroger les membres')

@section('content')
    <p class="mb-2">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:underline">← Mon tableau de bord</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">Membres</h1>

    <form method="GET" action="{{ route('dashboard.members') }}" class="mb-6 max-w-sm">
        <label for="q" class="block text-sm font-medium">Rechercher par nom ou fonction</label>
        <input id="q" type="text" name="q" value="{{ $query }}"
            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-2">Organisation</th>
                    <th class="px-4 py-2">Fonction</th>
                    <th class="px-4 py-2">Nom</th>
                    <th class="px-4 py-2">Courriel</th>
                    <th class="px-4 py-2">Cellulaire</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($memberRoles as $memberRole)
                    <tr class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-2 text-gray-500">{{ $memberRole->organization->name }}</td>
                        <td class="px-4 py-2">{{ $memberRole->role }}</td>
                        <td class="px-4 py-2 font-medium">{{ $memberRole->member->name }}</td>
                        <td class="px-4 py-2">{{ $memberRole->member->email }}</td>
                        <td class="px-4 py-2">{{ $memberRole->member->cell_phone ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucun membre trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
