@extends('layouts.app')

@section('title', 'Modifier ' . $organization->name)

@section('content')
    <p class="mb-2">
        <a href="{{ route('admin.organizations.index') }}" class="text-sm text-gray-500 hover:underline">← Toutes les organisations</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">{{ $organization->name }}</h1>
    <p class="mb-6 text-sm text-gray-500">Niveau {{ $organization->level->label() }}</p>

    <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="max-w-sm space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium">Nom de l'organisation</label>
            <input id="name" type="text" name="name" value="{{ old('name', $organization->name) }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label for="responsable_name" class="block text-sm font-medium">Nom du responsable</label>
            <input id="responsable_name" type="text" name="responsable_name"
                value="{{ old('responsable_name', $organization->responsable_name) }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label for="responsable_email" class="block text-sm font-medium">Courriel du responsable</label>
            <input id="responsable_email" type="email" name="responsable_email"
                value="{{ old('responsable_email', $organization->responsable_email) }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('responsable_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="address" class="block text-sm font-medium">Adresse</label>
            <input id="address" type="text" name="address" value="{{ old('address', $organization->address) }}"
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="business_number" class="block text-sm font-medium">Numéro d'entreprise</label>
            <input id="business_number" type="text" name="business_number"
                value="{{ old('business_number', $organization->business_number) }}"
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="website" class="block text-sm font-medium">Site web</label>
            <input id="website" type="url" name="website" value="{{ old('website', $organization->website) }}"
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('website')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
            Enregistrer
        </button>
    </form>
@endsection
