@extends('layouts.app')

@section('title', "Coordonnées de l'organisation")

@section('content')
    <div class="mx-auto max-w-lg rounded-lg border border-gray-200 bg-white p-6">
        <h1 class="mb-6 text-xl font-semibold">Coordonnées de l'organisation</h1>

        <form method="POST" action="{{ route('organizations.update', $organization) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium">Organisation</label>
                <input id="name" type="text" name="name" value="{{ old('name', $organization->name) }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="legal_name" class="block text-sm font-medium">Nom légal</label>
                <input id="legal_name" type="text" name="legal_name"
                    value="{{ old('legal_name', $organization->legal_name) }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('legal_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="address" class="block text-sm font-medium">Adresse</label>
                <input id="address" type="text" name="address" value="{{ old('address', $organization->address) }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="city" class="block text-sm font-medium">Ville</label>
                <input id="city" type="text" name="city" value="{{ old('city', $organization->city) }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="province" class="block text-sm font-medium">Province</label>
                <input id="province" type="text" name="province" value="{{ old('province', $organization->province) }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('province')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="postal_code" class="block text-sm font-medium">CP</label>
                <input id="postal_code" type="text" name="postal_code"
                    value="{{ old('postal_code', $organization->postal_code) }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('postal_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="business_number" class="block text-sm font-medium">No d'entreprise</label>
                <input id="business_number" type="text" name="business_number"
                    value="{{ old('business_number', $organization->business_number) }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('business_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="website" class="block text-sm font-medium">Site web</label>
                <input id="website" type="url" name="website" placeholder="https://…"
                    value="{{ old('website', $organization->website) }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('website')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3">
                <a href="{{ route('dashboard.properties') }}"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                    Modifier
                </button>
            </div>
        </form>
    </div>
@endsection
