@extends('layouts.app')

@section('title', 'Bottin')

@section('content')
    @include('partials.hero-banner', [
        'title' => 'Coordonnées des membres',
        'description' => 'Recherchez et filtrez les membres auxquels vous avez accès.',
    ])

    <div class="flex flex-col gap-6 lg:flex-row">
        <aside class="w-full lg:w-56 lg:shrink-0">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Filtres</p>

            @include('partials.member-filters', ['action' => route('bottin.index')])

            @if ($query !== '' || $regionId !== '' || $localId !== '' || $role !== '' || $myDirection)
                <a href="{{ route('bottin.index') }}"
                    class="mt-4 block rounded-md bg-gray-800 px-3 py-2 text-center text-sm font-medium text-white hover:bg-gray-900">
                    ✕ Réinitialiser
                </a>
            @endif
        </aside>

        <div class="flex-1">
            <form method="GET" action="{{ route('bottin.index') }}" class="mb-6 max-w-sm">
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

            <div class="space-y-3">
                @forelse ($memberRoles as $memberRole)
                    @php
                        $level = $memberRole->organization->level;
                        $badgeColor = match ($level->value) {
                            'provincial' => 'bg-indigo-100 text-indigo-700',
                            'regional' => 'bg-blue-100 text-blue-700',
                            default => 'bg-teal-100 text-teal-700',
                        };
                    @endphp
                    <div class="flex items-start gap-4 rounded-lg bg-white p-4 shadow-sm">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $badgeColor }} font-semibold">
                            {{ mb_substr($memberRole->member->name, 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium uppercase tracking-wide text-gray-600">
                                {{ $level->label() }}
                            </span>
                            <p class="mt-1 font-semibold text-gray-900">
                                {{ $memberRole->organization->name }}
                                <span class="text-gray-300">-</span> {{ $memberRole->role }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $memberRole->member->name }}
                                <span class="text-gray-300">-</span> {{ $memberRole->member->email }}
                                <span class="text-gray-300">-</span> {{ $memberRole->member->cell_phone ?: '—' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow-sm">
                        Aucun membre trouvé.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
