@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Connexion</h1>

    <p class="mb-6 text-sm text-gray-600">
        Entrez votre adresse courriel (membre ou responsable). Vous recevrez un lien de connexion valide 15 minutes.
    </p>

    <form method="POST" action="{{ route('login.bottin.store') }}" class="max-w-sm space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Adresse courriel</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-300 focus:outline-none"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
            Envoyer le lien de connexion
        </button>
    </form>
@endsection
