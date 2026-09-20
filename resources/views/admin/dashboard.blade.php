@extends('layouts.app')

@section('title', 'Administration')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Administration</h1>

    <div class="grid gap-4 sm:grid-cols-2">
        <a href="{{ route('admin.organizations.index') }}"
            class="rounded-lg border border-gray-200 bg-white p-5 hover:border-gray-400">
            <h2 class="font-semibold">Organisations</h2>
            <p class="mt-1 text-sm text-gray-500">
                Voir, créer, modifier, supprimer ou importer des organisations.
            </p>
        </a>

        <a href="{{ route('admin.members.index') }}"
            class="rounded-lg border border-gray-200 bg-white p-5 hover:border-gray-400">
            <h2 class="font-semibold">Membres</h2>
            <p class="mt-1 text-sm text-gray-500">
                Voir, ajouter, supprimer ou importer des membres.
            </p>
        </a>
    </div>
@endsection
