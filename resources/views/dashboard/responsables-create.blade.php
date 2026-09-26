@extends('layouts.app')

@section('title', 'Ajouter un responsable')

@section('content')
    <p class="mb-2">
        <a href="{{ route('dashboard.properties') }}" class="text-sm text-gray-500 hover:underline">← Mes propriétés</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">Ajouter un responsable du bottin</h1>

    <form method="POST" action="{{ route('responsables.store') }}" class="max-w-sm space-y-4">
        @csrf

        <div>
            <label for="responsable_name" class="block text-sm font-medium">Nom</label>
            <input id="responsable_name" type="text" name="name" value="{{ old('name') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="responsable_email" class="block text-sm font-medium">Courriel</label>
            <input id="responsable_email" type="email" name="email" value="{{ old('email') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="responsable_email_confirmation" class="block text-sm font-medium">Validation du courriel</label>
            <input id="responsable_email_confirmation" type="email" name="email_confirmation"
                value="{{ old('email_confirmation') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div class="flex gap-3">
            <div class="flex-1">
                <label for="responsable_cell_phone" class="block text-sm font-medium">Cellulaire / Téléphone</label>
                <input id="responsable_cell_phone" type="tel" inputmode="numeric" maxlength="14" name="cell_phone" value="{{ old('cell_phone') }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="w-28">
                <label for="responsable_extension" class="block text-sm font-medium">Poste</label>
                <input id="responsable_extension" type="text" inputmode="numeric" maxlength="10" name="extension" value="{{ old('extension') }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
        </div>
        <p class="text-xs text-gray-500">
            Si cette personne est déjà responsable d'une autre organisation, son nom et son téléphone déjà en fiche
            sont conservés.
        </p>

        <div class="flex gap-3">
            <a href="{{ route('dashboard.properties') }}"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Annuler
            </a>
            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Ajouter
            </button>
        </div>
    </form>
@endsection
