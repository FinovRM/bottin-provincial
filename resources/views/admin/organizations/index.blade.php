@extends('layouts.app')

@section('title', 'Organisations')

@section('content')
    <p class="mb-2">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:underline">← Administration</a>
    </p>

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

    <div class="mb-8 flex flex-col gap-6 lg:flex-row">
        <aside class="w-full lg:w-48 lg:shrink-0">
            <form method="GET" action="{{ route('admin.organizations.index') }}">
                @if ($query !== '')
                    <input type="hidden" name="q" value="{{ $query }}">
                @endif

                <div class="mb-4">
                    <label for="filter_level" class="block text-sm font-medium">Niveau</label>
                    <select id="filter_level" name="level" onchange="this.form.submit()"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Tous</option>
                        @foreach ($levels as $levelOption)
                            <option value="{{ $levelOption->value }}" @selected($level === $levelOption->value)>
                                {{ $levelOption->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="filter_parent_id" class="block text-sm font-medium">Organisation parente</label>
                    <select id="filter_parent_id" name="parent_id" onchange="this.form.submit()"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Toutes</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected($parentId === (string) $parent->id)>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_organization_id" class="block text-sm font-medium">Organisation</label>
                    <select id="filter_organization_id" name="organization_id" onchange="this.form.submit()"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Toutes</option>
                        @foreach ($organizationOptions as $organizationOption)
                            <option value="{{ $organizationOption->id }}" @selected($organizationId === (string) $organizationOption->id)>
                                {{ $organizationOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <noscript>
                    <button type="submit" class="mt-4 w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Filtrer
                    </button>
                </noscript>
            </form>

            @if ($query !== '' || $level !== '' || $parentId !== '' || $organizationId !== '')
                <a href="{{ route('admin.organizations.index') }}"
                    class="mt-4 block rounded-md bg-gray-800 px-3 py-2 text-center text-sm font-medium text-white hover:bg-gray-900">
                    ✕ Réinitialiser
                </a>
            @endif
        </aside>

        <div class="flex-1">
            <form method="GET" action="{{ route('admin.organizations.index') }}" class="mb-6 max-w-sm">
                @foreach (['level' => $level, 'parent_id' => $parentId, 'organization_id' => $organizationId] as $name => $value)
                    @if ($value !== '')
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
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
                                    {{ $organization->responsable_name }}
                                    <br><span class="text-gray-500">{{ $organization->responsable_email }}</span>
                                </td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.organizations.edit', $organization) }}" class="text-sm text-gray-700 hover:underline">Modifier</a>
                                    <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}"
                                        onsubmit="return confirm('Supprimer cette organisation ?');" class="ml-3 inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:underline">Supprimer</button>
                                    </form>
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
        </div>
    </div>
@endsection
