@extends('layouts.app')

@section('title', 'Créer une organisation')

@section('content')
    <p class="mb-2">
        <a href="{{ route('admin.organizations.index') }}" class="text-sm text-gray-500 hover:underline">← Toutes les organisations</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">Créer une organisation</h1>

    <form method="POST" action="{{ route('admin.organizations.store') }}" class="max-w-sm space-y-4">
        @csrf

        <div>
            <label for="level" class="block text-sm font-medium">Niveau</label>
            <select id="level" name="level" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">— Choisir —</option>
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}" @selected(old('level') === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
            @error('level')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="parent_id" class="block text-sm font-medium">Organisation parente</label>
            <select id="parent_id" name="parent_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">— Aucune (provincial) —</option>
                @foreach ($organizations as $parent)
                    <option value="{{ $parent->id }}" @selected((int) old('parent_id') === $parent->id)>
                        {{ $parent->name }} ({{ $parent->level->label() }})
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">
                Laisser vide seulement pour une organisation provinciale.
            </p>
            @error('parent_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-medium">Nom de l'organisation</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label for="responsable_name" class="block text-sm font-medium">Nom du responsable</label>
            <input id="responsable_name" type="text" name="responsable_name"
                value="{{ old('responsable_name') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label for="responsable_email" class="block text-sm font-medium">Courriel du responsable</label>
            <input id="responsable_email" type="email" name="responsable_email" value="{{ old('responsable_email') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('responsable_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
            Créer
        </button>
    </form>
@endsection
