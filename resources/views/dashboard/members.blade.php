@extends('layouts.app')

@section('title', 'Coordonnées des membres')

@section('content')
    @include('partials.hero-banner', [
        'title' => 'Coordonnées des membres',
        'description' => 'Recherchez et filtrez les membres des organisations sous votre responsabilité.',
    ])

    <p class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:underline">← Mon tableau de bord</a>
    </p>

    <div class="flex flex-col gap-6 lg:flex-row">
        <aside class="w-full lg:w-56 lg:shrink-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Filtres</p>

            @include('partials.member-filters', ['action' => route('dashboard.members')])

            @if ($query !== '' || $regionId !== '' || $localId !== '' || $role !== '' || $myDirection)
                <a href="{{ route('dashboard.members') }}"
                    class="mt-4 block rounded-md bg-gray-800 px-3 py-2 text-center text-sm font-medium text-white hover:bg-gray-900">
                    ✕ Réinitialiser
                </a>
            @endif
        </aside>

        <div class="flex-1">
            <form method="GET" action="{{ route('dashboard.members') }}" class="mb-6 max-w-sm">
                @if ($myDirection)
                    <input type="hidden" name="my_direction" value="1">
                @endif
                @if ($regionId !== '')
                    <input type="hidden" name="region_id" value="{{ $regionId }}">
                @endif
                @if ($localId !== '')
                    <input type="hidden" name="local_id" value="{{ $localId }}">
                @endif
                @if ($role !== '')
                    <input type="hidden" name="role" value="{{ $role }}">
                @endif
                <label for="q" class="block text-sm font-medium text-gray-500">Recherche par mots clés</label>
                <div class="relative mt-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                    <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Nom, fonction…"
                        class="w-full rounded-md border border-gray-300 py-2 pl-9 pr-3 text-sm">
                </div>
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
        </div>
    </div>
@endsection
