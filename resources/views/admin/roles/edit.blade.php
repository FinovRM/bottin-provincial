@extends('layouts.app')

@section('title', 'Modifier ' . $role->name)

@section('content')
    <p class="mb-2">
        <a href="{{ route('admin.roles.index') }}" class="text-sm text-gray-500 hover:underline">← Tous les rôles</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">{{ $role->name }}</h1>
    <p class="mb-6 text-sm text-gray-500">Niveau {{ $role->level->label() }}</p>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="max-w-sm space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium">Nom du rôle</label>
            <input id="name" type="text" name="name" value="{{ old('name', $role->name) }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
            Enregistrer
        </button>
    </form>
@endsection
