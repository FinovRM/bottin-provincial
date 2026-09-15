@extends('layouts.app')

@section('title', 'Mon tableau de bord')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ $organization->name }}</h1>

    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Ma fiche ({{ $organization->level->label() }})</h2>

        <form method="POST" action="{{ route('organizations.update', $organization) }}" class="max-w-sm space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium">Nom de l'organisation</label>
                <input id="name" type="text" name="name" value="{{ old('name', $organization->name) }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3">
                <div class="flex-1">
                    <label for="responsable_first_name" class="block text-sm font-medium">Prénom</label>
                    <input id="responsable_first_name" type="text" name="responsable_first_name"
                        value="{{ old('responsable_first_name', $organization->responsable_first_name) }}" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div class="flex-1">
                    <label for="responsable_last_name" class="block text-sm font-medium">Nom</label>
                    <input id="responsable_last_name" type="text" name="responsable_last_name"
                        value="{{ old('responsable_last_name', $organization->responsable_last_name) }}" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label for="responsable_email" class="block text-sm font-medium">Adresse courriel du responsable</label>
                <input id="responsable_email" type="email" name="responsable_email"
                    value="{{ old('responsable_email', $organization->responsable_email) }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">
                    Modifier ce courriel transfère l'accès à cette fiche à la nouvelle adresse.
                </p>
                @error('responsable_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Enregistrer
            </button>
        </form>
    </section>

    @if ($organization->canCreateChildren())
        <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-semibold">
                Organisations {{ $organization->level->childLevel()->pluralLabel() }}
            </h2>

            @forelse ($organization->children as $child)
                <div class="mb-2 flex items-center justify-between border-b border-gray-100 pb-2 last:border-0">
                    <div>
                        <p class="font-medium">{{ $child->name }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $child->responsable_first_name }} {{ $child->responsable_last_name }} —
                            {{ $child->responsable_email }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('organizations.destroy', $child) }}"
                        onsubmit="return confirm('Retirer cette organisation ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:underline">Retirer</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500">Aucune organisation de niveau {{ $organization->level->childLevel()->label() }} pour l'instant.</p>
            @endforelse

            <h3 class="mt-6 mb-3 text-sm font-semibold">Ajouter une organisation de niveau {{ $organization->level->childLevel()->label() }}</h3>

            <form method="POST" action="{{ route('organizations.store') }}" class="max-w-sm space-y-4">
                @csrf

                <div>
                    <label for="child_name" class="block text-sm font-medium">Nom de l'organisation</label>
                    <input id="child_name" type="text" name="name" value="{{ old('name') }}" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div class="flex gap-3">
                    <div class="flex-1">
                        <label for="child_first_name" class="block text-sm font-medium">Prénom du responsable</label>
                        <input id="child_first_name" type="text" name="responsable_first_name"
                            value="{{ old('responsable_first_name') }}" required
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="flex-1">
                        <label for="child_last_name" class="block text-sm font-medium">Nom du responsable</label>
                        <input id="child_last_name" type="text" name="responsable_last_name"
                            value="{{ old('responsable_last_name') }}" required
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label for="child_email" class="block text-sm font-medium">Courriel du responsable</label>
                    <input id="child_email" type="email" name="responsable_email"
                        value="{{ old('responsable_email') }}" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    @error('responsable_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                    Ajouter
                </button>
            </form>
        </section>
    @endif
@endsection
