@extends('layouts.app')

@section('title', 'Déclaration du responsable')

@section('content')
    <div class="mx-auto max-w-lg rounded-lg border border-gray-200 bg-white p-6">
        <h1 class="mb-4 text-xl font-semibold">Déclaration du responsable</h1>

        <p class="mb-6 text-sm text-gray-700">
            Je m'engage à vérifier et à n'inscrire aucune donnée sensible, notamment un numéro de
            téléphone/cellulaire confidentiel, dans les informations de l'organisation dont j'ai la responsabilité.
        </p>

        <form method="POST" action="{{ url()->full() }}">
            @csrf

            <div class="mb-2">
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="responsable_confirmed" value="1" required class="mt-0.5">
                    <span>Aucune donnée confidentielle ne sera inscrite</span>
                </label>
                @error('responsable_confirmed')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4 flex gap-3">
                <a href="{{ route('dashboard') }}"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                    Entrer
                </button>
            </div>
        </form>
    </div>
@endsection
