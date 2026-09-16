@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Connexion</h1>

    <div class="grid gap-4 sm:grid-cols-2">
        <a href="{{ route('login.bottin') }}"
            class="rounded-lg border border-gray-200 bg-white p-5 hover:border-gray-400">
            <h2 class="font-semibold">Bottin</h2>
            <p class="mt-1 text-sm text-gray-500">
                Consulter les coordonnées des membres — pour les membres, et pour les responsables qui veulent
                voir le bottin.
            </p>
        </a>

        <a href="{{ route('login.editeur') }}"
            class="rounded-lg border border-gray-200 bg-white p-5 hover:border-gray-400">
            <h2 class="font-semibold">Éditeur</h2>
            <p class="mt-1 text-sm text-gray-500">
                Réservé aux responsables : gérer les organisations et les membres sous votre responsabilité.
            </p>
        </a>
    </div>
@endsection
