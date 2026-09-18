@extends('layouts.app')

@section('title', 'Rôles')

@section('content')
    <p class="mb-2">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:underline">← Administration</a>
    </p>

    <h1 class="mb-6 text-2xl font-semibold">Rôles</h1>

    <p class="mb-6 text-sm text-gray-500">
        Définition des rôles minimum à compléter des niveaux provinciaux, régionaux et locaux.
    </p>

    <div class="space-y-6">
        @foreach ($sections as $section)
            <section>
                <h2 class="mb-2 font-semibold">{{ $section['title'] }}</h2>

                <div class="rounded-lg border border-gray-200 bg-white">
                    <ul>
                        @forelse ($section['roles'] as $role)
                            <li class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-3 text-sm last:border-0">
                                <span>{{ $role->name }}</span>
                                <span class="flex shrink-0 gap-3">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="text-gray-500 hover:underline">Modifier</a>
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                        onsubmit="return confirm('Supprimer ce rôle ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                                    </form>
                                </span>
                            </li>
                        @empty
                            <li class="px-5 py-3 text-sm text-gray-500">Aucun rôle défini.</li>
                        @endforelse
                    </ul>
                </div>

                <form method="POST" action="{{ route('admin.roles.store') }}" class="mt-3 flex max-w-sm gap-2">
                    @csrf
                    <input type="hidden" name="level" value="{{ $section['level']->value }}">
                    <div class="flex-1">
                        <input type="text" name="name" value="{{ old('level') === $section['level']->value ? old('name') : '' }}"
                            placeholder="Nom du rôle" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        @if (old('level') === $section['level']->value)
                            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @endif
                    </div>
                    <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                        Ajouter
                    </button>
                </form>
            </section>
        @endforeach
    </div>
@endsection
