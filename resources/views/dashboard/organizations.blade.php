@extends('layouts.app')

@section('title', 'Interroger les organisations')

@section('content')
    <p class="mb-2">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:underline">← Mon tableau de bord</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">Organisations</h1>

    <form method="GET" action="{{ route('dashboard.organizations') }}" class="mb-6 max-w-sm">
        <label for="q" class="block text-sm font-medium">Rechercher par nom</label>
        <input id="q" type="text" name="q" value="{{ $query }}"
            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-2">Organisation</th>
                    <th class="px-4 py-2">Niveau</th>
                    <th class="px-4 py-2">Responsable</th>
                    <th class="px-4 py-2">Adresse</th>
                    <th class="px-4 py-2">N° d'entreprise</th>
                    <th class="px-4 py-2">Site web</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($organizations as $organization)
                    <tr class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-2 font-medium">{{ $organization->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $organization->level->label() }}</td>
                        <td class="px-4 py-2">
                            {{ $organization->responsable_first_name }} {{ $organization->responsable_last_name }}
                            <br><span class="text-gray-500">{{ $organization->responsable_email }}</span>
                        </td>
                        <td class="px-4 py-2">{{ $organization->address ?: '—' }}</td>
                        <td class="px-4 py-2">{{ $organization->business_number ?: '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($organization->website)
                                <a href="{{ $organization->website }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ $organization->website }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucune organisation trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
