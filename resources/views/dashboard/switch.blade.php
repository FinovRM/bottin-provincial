@extends('layouts.app')

@section('title', 'Choisir une organisation')

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">Choisir une organisation</h1>
    <p class="mb-6 text-sm text-gray-600">
        Vous êtes responsable de plusieurs organisations. Laquelle voulez-vous gérer ?
    </p>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($organizations as $organization)
            <form method="POST" action="{{ route('dashboard.switch.store', $organization) }}">
                @csrf
                <button type="submit"
                    class="w-full rounded-lg border p-5 text-left hover:border-gray-400 {{ $current->is($organization) ? 'border-gray-400 bg-gray-50' : 'border-gray-200 bg-white' }}">
                    <h2 class="font-semibold">{{ $organization->name }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $organization->level->label() }}</p>
                </button>
            </form>
        @endforeach
    </div>
@endsection
