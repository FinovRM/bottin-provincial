@extends('layouts.app')

@section('title', 'Connexion administrateur')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Connexion administrateur</h1>

    <form method="POST" action="{{ route('admin.login.store') }}" class="max-w-sm space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Courriel</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Mot de passe</label>
            <input id="password" type="password" name="password" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
            Se connecter
        </button>
    </form>
@endsection
