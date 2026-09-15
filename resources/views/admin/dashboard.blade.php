@extends('layouts.app')

@section('title', 'Administration')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Toutes les organisations</h1>
        <div class="flex gap-3 text-sm">
            <a href="{{ route('admin.organizations.create') }}" class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-black">
                Créer une organisation
            </a>
            <a href="{{ route('admin.organizations.import.create') }}" class="rounded-md border border-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-50">
                Importer un CSV
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.dashboard') }}" class="mb-6 max-w-sm">
        <label for="q" class="block text-sm font-medium">Rechercher par nom ou courriel</label>
        <input id="q" type="text" name="q" value="{{ $query }}"
            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-2">Organisation</th>
                    <th class="px-4 py-2">Niveau</th>
                    <th class="px-4 py-2">Organisation parente</th>
                    <th class="px-4 py-2">Responsable</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($organizations as $organization)
                    <tr class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-2 font-medium">{{ $organization->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $organization->level->label() }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $organization->parent?->name ?? '—' }}</td>
                        <td class="px-4 py-2">
                            {{ $organization->responsable_first_name }} {{ $organization->responsable_last_name }}
                            <br><span class="text-gray-500">{{ $organization->responsable_email }}</span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.organizations.edit', $organization) }}" class="text-sm text-gray-700 hover:underline">Modifier</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucune organisation trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
