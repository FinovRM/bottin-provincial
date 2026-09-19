@extends('layouts.app')

@section('title', 'Ajouter une organisation locale')

@section('content')
    <div class="mx-auto max-w-lg rounded-lg border border-gray-200 bg-white p-6">
        <h1 class="mb-6 text-xl font-semibold">Ajouter une organisation locale</h1>

        <form method="POST" action="{{ route('organizations.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium">Nom de l'organisation</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="responsable_name" class="block text-sm font-medium">Nom du responsable</label>
                <input id="responsable_name" type="text" name="responsable_name" value="{{ old('responsable_name') }}"
                    required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('responsable_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="responsable_email" class="block text-sm font-medium">Courriel du responsable</label>
                <input id="responsable_email" type="email" name="responsable_email"
                    value="{{ old('responsable_email') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('responsable_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="responsable_email_confirmation" class="block text-sm font-medium">Validation du courriel</label>
                <input id="responsable_email_confirmation" type="email" name="responsable_email_confirmation"
                    value="{{ old('responsable_email_confirmation') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>

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
    </div>
@endsection
