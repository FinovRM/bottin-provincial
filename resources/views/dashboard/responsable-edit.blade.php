@extends('layouts.app')

@section('title', 'Modifier le responsable')

@section('content')
    <p class="mb-2">
        <a href="{{ route('dashboard.properties') }}" class="text-sm text-gray-500 hover:underline">← Mes propriétés</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">Modifier le responsable du bottin</h1>

    <form method="POST" action="{{ route('responsable.update') }}" class="max-w-sm space-y-4">
        @csrf

        <div>
            <label for="responsable_name" class="block text-sm font-medium">Nom</label>
            <input id="responsable_name" type="text" name="responsable_name"
                value="{{ old('responsable_name', $organization->responsable_name) }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('responsable_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="responsable_email" class="block text-sm font-medium">Adresse courriel</label>
            <input id="responsable_email" type="email" name="responsable_email" value="{{ old('responsable_email') }}"
                required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('responsable_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="responsable_email_confirmation" class="block text-sm font-medium">Confirmer l'adresse courriel</label>
            <input id="responsable_email_confirmation" type="email" name="responsable_email_confirmation"
                value="{{ old('responsable_email_confirmation') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-gray-500">
                Modifier ce courriel transfère l'accès à cette fiche à la nouvelle adresse.
            </p>
        </div>

        <div>
            <label for="responsable_cell_phone" class="block text-sm font-medium">Cellulaire</label>
            <input id="responsable_cell_phone" type="text" name="responsable_cell_phone"
                value="{{ old('responsable_cell_phone', $organization->responsable_cell_phone) }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('responsable_cell_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex gap-3">
            <a href="{{ route('dashboard.properties') }}"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Abandonner
            </a>
            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Accepter
            </button>
        </div>
    </form>
@endsection
