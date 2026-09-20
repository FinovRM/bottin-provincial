@extends('layouts.app')

@section('title', 'Bottin de communication')

@section('content')
    @include('partials.hero-banner', [
        'title' => 'Bottin de communication',
        'description' => 'Consultez les organisations affiliées et leurs coordonnées.',
    ])

    @forelse ($provincialOrganizations as $provincial)
        <section class="mb-10">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700">
                    {{ mb_substr($provincial->name, 0, 1) }}
                </span>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $provincial->name }}</h2>
                    <span class="inline-block rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium uppercase tracking-wide text-indigo-700">
                        Provincial
                    </span>
                </div>
            </div>

            @if ($provincial->children->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($provincial->children as $regional)
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">
                            <div class="mb-3 flex items-center gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                                    {{ mb_substr($regional->name, 0, 1) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-gray-900">{{ $regional->name }}</p>
                                    <span class="inline-block rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium uppercase tracking-wide text-blue-700">
                                        Régional
                                    </span>
                                </div>
                            </div>

                            @if ($regional->children->isNotEmpty())
                                <details class="group">
                                    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-medium text-gray-500">
                                        <span>
                                            {{ $regional->children->count() }}
                                            {{ $regional->children->count() > 1 ? 'organisations locales' : 'organisation locale' }}
                                        </span>
                                        <svg class="h-4 w-4 shrink-0 text-gray-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </summary>
                                    <ul class="mt-2 max-h-48 space-y-0.5 overflow-y-auto border-t border-gray-100 pt-2 text-sm text-gray-700">
                                        @foreach ($regional->children as $local)
                                            <li class="rounded px-1.5 py-1 odd:bg-gray-50">{{ $local->name }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @else
                                <p class="text-sm text-gray-400">Aucune organisation locale.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @empty
        <p class="text-gray-500">Aucune organisation n'a encore été inscrite.</p>
    @endforelse
@endsection
