@extends('layouts.app')

@section('title', 'Organisations')

@section('content')
    @include('partials.hero-banner', [
        'title' => 'Organisations',
        'description' => 'Recherchez les organisations de votre niveau, tous groupes confondus.',
    ])

    <p class="mb-6">
        <a href="{{ route('bottin.index') }}" class="text-sm text-gray-500 hover:underline">← Bottin des membres</a>
    </p>

    <form method="GET" action="{{ route('bottin.organizations') }}" class="mb-6 max-w-sm">
        <label for="q" class="block text-sm font-medium text-gray-500">Recherche par nom</label>
        <div class="relative mt-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
            </svg>
            <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Nom de l'organisation…"
                class="w-full rounded-md border border-gray-300 py-2 pl-9 pr-3 text-sm">
        </div>
    </form>

    <div class="space-y-3">
        @forelse ($organizations as $organization)
            @php
                $levelBadge = match ($organization->level->value) {
                    'provincial' => 'bg-indigo-100 text-indigo-700',
                    'regional' => 'bg-blue-100 text-blue-700',
                    default => 'bg-teal-100 text-teal-700',
                };
                $groupBadge = $organization->group->value === 'ligue' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600';
            @endphp
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $levelBadge }} font-semibold">
                            {{ mb_substr($organization->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium uppercase tracking-wide text-gray-600">
                                    {{ $organization->level->label() }}
                                </span>
                                <span class="inline-block rounded-full {{ $groupBadge }} px-2 py-0.5 text-xs font-medium">
                                    {{ $organization->group->label() }}
                                </span>
                            </div>
                            <p class="mt-1 font-semibold text-gray-900">{{ $organization->name }}</p>
                        </div>
                    </div>
                </div>

                <dl class="mt-3 grid gap-x-6 gap-y-2 border-t border-gray-100 pt-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-400">Adresse</dt>
                        <dd class="text-gray-700">{{ $organization->fullPostalAddress() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-400">Responsable</dt>
                        <dd class="text-gray-700">
                            {{ $organization->responsable_name }}
                            <span class="text-gray-300">·</span>
                            <a href="mailto:{{ $organization->responsable_email }}" class="hover:underline">{{ $organization->responsable_email }}</a>
                        </dd>
                    </div>
                </dl>
            </div>
        @empty
            <div class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow-sm">
                Aucune organisation trouvée.
            </div>
        @endforelse
    </div>
@endsection
