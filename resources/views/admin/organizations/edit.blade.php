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
                    <option value="{{ $parent->id }}" data-level="{{ $parent->level->value }}" @selected((int) old('parent_id', $organization->parent_id) === $parent->id)>
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

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
            Enregistrer
        </button>
    </form>

    <section class="mt-10 max-w-3xl">
        <h2 class="mb-4 text-lg font-semibold">Responsable(s) du bottin</h2>

        <div class="mb-6 overflow-x-auto rounded-lg border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2">Nom</th>
                        <th class="px-4 py-2">Courriel</th>
                        <th class="px-4 py-2">Cellulaire / Téléphone</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($organization->responsables as $responsable)
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="px-4 py-2 font-medium">{{ $responsable->name }}</td>
                            <td class="px-4 py-2">{{ $responsable->email }}</td>
                            <td class="px-4 py-2"><x-member-phone :member="$responsable" fallback="—" /></td>
                            <td class="px-4 py-2 text-right">
                                @if ($organization->responsables->count() > 1)
                                    <form method="POST" action="{{ route('admin.responsables.destroy', $responsable) }}"
                                        onsubmit="return confirm('Retirer ce responsable ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:underline">Retirer</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h3 class="mb-3 font-semibold">Ajouter un responsable</h3>
        <form method="POST" action="{{ route('admin.organizations.responsables.store', $organization) }}" class="max-w-sm space-y-4">
            @csrf
            <div>
                <label for="new_responsable_name" class="block text-sm font-medium">Nom</label>
                <input id="new_responsable_name" type="text" name="responsable_name" value="{{ old('responsable_name') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('responsable_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="new_responsable_email" class="block text-sm font-medium">Courriel</label>
                <input id="new_responsable_email" type="email" name="responsable_email" value="{{ old('responsable_email') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('responsable_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label for="new_responsable_cell_phone" class="block text-sm font-medium">Cellulaire / Téléphone</label>
                    <input id="new_responsable_cell_phone" type="tel" inputmode="numeric" maxlength="14" name="responsable_cell_phone" value="{{ old('responsable_cell_phone') }}"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div class="w-28">
                    <label for="new_responsable_extension" class="block text-sm font-medium">Poste</label>
                    <input id="new_responsable_extension" type="text" inputmode="numeric" maxlength="10" name="responsable_extension" value="{{ old('responsable_extension') }}"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>
            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Ajouter
            </button>
        </form>
    </section>

    @include('partials.parent-level-filter')
@endsection
