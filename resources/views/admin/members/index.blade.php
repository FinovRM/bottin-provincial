@extends('layouts.app')

@section('title', 'Membres')

@section('content')
    <p class="mb-2">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:underline">← Administration</a>
    </p>

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Tous les membres</h1>
        <a href="{{ route('admin.members.import.create') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Importer un CSV
        </a>
    </div>

    <form method="GET" action="{{ route('admin.members.index') }}" class="mb-6 max-w-sm">
        <label for="q" class="block text-sm font-medium">Rechercher par nom, fonction ou courriel</label>
        <input id="q" type="text" name="q" value="{{ $query }}"
            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
    </form>

    <div class="mb-8 overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-2">Organisation</th>
                    <th class="px-4 py-2">Fonction</th>
                    <th class="px-4 py-2">Nom</th>
                    <th class="px-4 py-2">Courriel</th>
                    <th class="px-4 py-2">Cellulaire</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($memberRoles as $memberRole)
                    <tr class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-2 text-gray-500">{{ $memberRole->organization->name }}</td>
                        <td class="px-4 py-2">{{ $memberRole->role }}</td>
                        <td class="px-4 py-2 font-medium">{{ $memberRole->member->name }}</td>
                        <td class="px-4 py-2">{{ $memberRole->member->email }}</td>
                        <td class="px-4 py-2">{{ $memberRole->member->cell_phone ?: '—' }}</td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('admin.members.destroy', $memberRole) }}"
                                onsubmit="return confirm('Retirer ce rôle ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Retirer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucun membre trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Ajouter un rôle</h2>
        <p class="mb-4 text-xs text-gray-500">
            Une personne (un courriel) peut occuper plusieurs rôles. Si ce courriel est déjà enregistré, le nom et
            le cellulaire déjà en fiche sont conservés — seul le rôle s'ajoute.
        </p>

        <form method="POST" action="{{ route('admin.members.store') }}" class="max-w-sm space-y-4">
            @csrf

            <div>
                <label for="organization_id" class="block text-sm font-medium">Organisation</label>
                <select id="organization_id" name="organization_id" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">— Choisir —</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected((int) old('organization_id') === $organization->id)>
                            {{ $organization->name }} ({{ $organization->level->label() }})
                        </option>
                    @endforeach
                </select>
                @error('organization_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium">Fonction</label>
                <input id="role" type="text" name="role" value="{{ old('role') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium">Courriel</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="name" class="block text-sm font-medium">Nom</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="cell_phone" class="block text-sm font-medium">Cellulaire</label>
                <input id="cell_phone" type="text" name="cell_phone" value="{{ old('cell_phone') }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>

            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Ajouter
            </button>
        </form>
    </section>
@endsection
