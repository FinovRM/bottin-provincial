@extends('layouts.app')

@section('title', 'Modifier ' . $organization->name)

@section('content')
    <p class="mb-2">
        <a href="{{ route('admin.organizations.index') }}" class="text-sm text-gray-500 hover:underline">← Toutes les organisations</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">{{ $organization->name }}</h1>

    <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="max-w-sm space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="level" class="block text-sm font-medium">Niveau de l'organisation</label>
            <select id="level" name="level" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}" @selected(old('level', $organization->level->value) === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
            @error('level')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="parent_id" class="block text-sm font-medium">Organisation parente</label>
            <select id="parent_id" name="parent_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">— Aucune (provincial) —</option>
                @foreach ($organizations as $parent)
                    <option value="{{ $parent->id }}" @selected((int) old('parent_id', $organization->parent_id) === $parent->id)>
                        {{ $parent->name }} ({{ $parent->level->label() }})
                    </option>
                @endforeach
            </select>
            @error('parent_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

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
            <label for="responsable_cell_phone" class="block text-sm font-medium">Cellulaire du responsable</label>
            <input id="responsable_cell_phone" type="tel" inputmode="numeric" maxlength="14" name="responsable_cell_phone"
                value="{{ old('responsable_cell_phone', $organization->responsable_cell_phone) }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('responsable_cell_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
            Enregistrer
        </button>
    </form>
@endsection
