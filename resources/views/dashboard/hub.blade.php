@extends('layouts.app')

@section('title', 'Mon tableau de bord')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Mon tableau de bord</h1>

    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('dashboard.properties') }}"
            class="rounded-lg border border-gray-200 bg-white p-5 hover:border-gray-400">
            <h2 class="font-semibold">Compléter mes propriétés</h2>
            <p class="mt-1 text-sm text-gray-500">
                Ma fiche, les informations de mon organisation, les organisations que je gère et mes membres.
            </p>
        </a>

        <a href="{{ route('dashboard.organizations') }}"
            class="rounded-lg border border-gray-200 bg-white p-5 hover:border-gray-400">
            <h2 class="font-semibold">Interroger les organisations</h2>
            <p class="mt-1 text-sm text-gray-500">
                Adresse, numéro d'entreprise et site web des organisations sous ma responsabilité.
            </p>
        </a>

        <a href="{{ route('dashboard.members') }}"
            class="rounded-lg border border-gray-200 bg-white p-5 hover:border-gray-400">
            <h2 class="font-semibold">Interroger les membres</h2>
            <p class="mt-1 text-sm text-gray-500">
                Coordonnées des membres des organisations sous ma responsabilité.
            </p>
        </a>
    </div>
@endsection
