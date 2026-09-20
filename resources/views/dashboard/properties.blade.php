@extends('layouts.app')

@section('title', 'Mes propriétés')

@section('content')
    @include('partials.hero-banner', [
        'title' => $organization->name,
        'description' => 'Gérez le responsable, les coordonnées et les membres de votre organisation.',
        'meta' => 'Mise à jour : '.$organization->updated_at->format('Y-m-d à H:i'),
    ])

    <p class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:underline">← Mon tableau de bord</a>
    </p>

    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Responsable du bottin</h2>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2">Fonction</th>
                        <th class="px-4 py-2">Nom</th>
                        <th class="px-4 py-2">Courriel</th>
                        <th class="px-4 py-2">Cellulaire</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-2">Responsable du bottin</td>
                        <td class="px-4 py-2 font-medium">{{ $organization->responsable_name }}</td>
                        <td class="px-4 py-2">{{ $organization->responsable_email }}</td>
                        <td class="px-4 py-2">{{ $organization->responsable_cell_phone ?: '—' }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('responsable.edit') }}" class="text-sm text-gray-700 hover:underline">Modifier</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Coordonnées de l'organisation ({{ $organization->level->label() }})</h2>
            <a href="{{ route('organizations.edit') }}" class="text-sm text-gray-700 hover:underline">Modifier</a>
        </div>

        <dl class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-1 text-sm text-gray-700">
            <dt class="text-right font-medium">Organisation :</dt>
            <dd>{{ $organization->name }}</dd>

            <dt class="text-right font-medium">Nom légal :</dt>
            <dd>{{ $organization->legal_name ?: '—' }}</dd>

            <dt class="text-right font-medium">Adresse :</dt>
            <dd>{{ $organization->address ?: '—' }}</dd>

            <dt class="text-right font-medium">Ville :</dt>
            <dd>{{ $organization->city ?: '—' }}</dd>

            <dt class="text-right font-medium">Province :</dt>
            <dd>{{ $organization->province ?: '—' }}</dd>

            <dt class="text-right font-medium">CP :</dt>
            <dd>{{ $organization->postal_code ?: '—' }}</dd>

            <dt class="text-right font-medium">No d'entreprise :</dt>
            <dd>{{ $organization->business_number ?: '—' }}</dd>

            <dt class="text-right font-medium">Site web :</dt>
            <dd>{{ $organization->website ?: '—' }}</dd>
        </dl>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Membres de {{ $organization->name }}</h2>
        <p class="mb-4 text-xs text-gray-500">
            Une personne (un courriel) peut occuper plusieurs rôles ; son nom et son cellulaire ne se saisissent
            qu'une fois et restent ensuite fixes. Une fiche ne se modifie pas une fois enregistrée — au besoin,
            retirez-la et ajoutez-en une nouvelle.
        </p>

        @if ($missingRoles->isNotEmpty())
            <div class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Rôle(s) minimum manquant(s) : {{ $missingRoles->implode(', ') }}.
            </div>
        @endif

        <div class="mb-6 overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2">Fonction</th>
                        <th class="px-4 py-2">Nom</th>
                        <th class="px-4 py-2">Courriel</th>
                        <th class="px-4 py-2">Cellulaire</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organization->memberRoles as $memberRole)
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="px-4 py-2">{{ $memberRole->role }}</td>
                            <td class="px-4 py-2 font-medium">{{ $memberRole->member->name }}</td>
                            <td class="px-4 py-2">{{ $memberRole->member->email }}</td>
                            <td class="px-4 py-2">{{ $memberRole->member->cell_phone ?: '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                <form method="POST" action="{{ route('members.destroy', $memberRole) }}"
                                    onsubmit="return confirm('Retirer ce rôle ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">Retirer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucun membre pour l'instant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <a href="{{ route('members.create') }}"
            class="inline-block rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
            Ajouter un membre
        </a>
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
                            {{ $child->responsable_name }} —
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

            <a href="{{ route('organizations.create') }}"
                class="inline-block rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Ajouter une organisation locale
            </a>
        </section>
    @endif
@endsection
