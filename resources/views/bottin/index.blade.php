@extends('layouts.app')

@section('title', 'Bottin de communication')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Bottin de communication</h1>

    @forelse ($provincialOrganizations as $provincial)
        <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="text-lg font-semibold">{{ $provincial->name }}</h2>
            <p class="text-sm text-gray-500">Provincial</p>

            @if ($provincial->children->isNotEmpty())
                <ul class="mt-4 grid gap-4 sm:grid-cols-3">
                    @foreach ($provincial->children as $regional)
                        <li class="rounded-md border border-gray-200 p-3">
                            <p class="font-medium">{{ $regional->name }}</p>
                            <p class="text-sm text-gray-500">Régional</p>

                            @if ($regional->children->isNotEmpty())
                                <ul class="mt-2 space-y-1 border-l border-gray-200 pl-4">
                                    @foreach ($regional->children as $local)
                                        <li class="text-sm text-gray-700">
                                            {{ $local->name }}
                                            <span class="text-gray-400">— Local</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @empty
        <p class="text-gray-500">Aucune organisation n'a encore été inscrite.</p>
    @endforelse
@endsection
