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
        <div class="mb-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </span>
                <h2 class="text-lg font-semibold">Responsable du bottin</h2>
            </div>
            <a href="{{ route('responsable.edit') }}" class="shrink-0 text-sm text-gray-700 hover:underline">Modifier</a>
        </div>

        <dl class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-1 text-sm text-gray-700">
            <dt class="text-right font-medium">Nom :</dt>
            <dd>{{ $organization->responsable_name }}</dd>

            <dt class="text-right font-medium">Courriel :</dt>
            <dd><a href="mailto:{{ $organization->responsable_email }}" class="hover:underline">{{ $organization->responsable_email }}</a></dd>

            <dt class="text-right font-medium">Cellulaire :</dt>
            <dd>{{ $organization->responsable_cell_phone ?: '—' }}</dd>
        </dl>
    </section>

    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                </span>
                <h2 class="text-lg font-semibold">Coordonnées ({{ $organization->level->label() }})</h2>
            </div>
            <a href="{{ route('organizations.edit') }}" class="shrink-0 text-sm text-gray-700 hover:underline">Modifier</a>
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
            <dd>
                @if ($organization->website)
                    <a href="{{ $organization->website }}" target="_blank" rel="noopener" class="hover:underline">{{ $organization->website }} ↗</a>
                @else
                    —
                @endif
            </dd>
        </dl>
    </section>

    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
        <div class="mb-1 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M15 19.128L15 19.128m0-11.628a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </span>
                <h2 class="text-lg font-semibold">
                    Membres
                    <span class="ml-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                        {{ $organization->memberRoles->count() }}
                    </span>
                </h2>
            </div>
            <a href="{{ route('members.create') }}"
                class="inline-flex shrink-0 items-center gap-1 rounded-md bg-gray-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-black">
                + Ajouter
            </a>
        </div>
        <p class="mb-4 text-xs text-gray-500">
            Une personne (un courriel) peut occuper plusieurs rôles ; son nom et son cellulaire ne se saisissent
            qu'une fois et restent ensuite fixes. Une fiche ne se modifie pas une fois enregistrée — au besoin,
            retirez-la et ajoutez-en une nouvelle.
        </p>

        @if ($missingRoles->isNotEmpty())
            <div class="mb-4 flex items-start gap-2 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <span>Rôle(s) minimum manquant(s) : {{ $missingRoles->implode(', ') }}.</span>
            </div>
        @endif

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
                    @forelse ($organization->memberRoles as $memberRole)
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="px-4 py-2">{{ $memberRole->role }}</td>
                            <td class="px-4 py-2 font-medium">{{ $memberRole->member->name }}</td>
                            <td class="px-4 py-2"><a href="mailto:{{ $memberRole->member->email }}" class="hover:underline">{{ $memberRole->member->email }}</a></td>
                            <td class="px-4 py-2">
                                @if ($memberRole->member->cell_phone)
                                    <a href="tel:{{ $memberRole->member->cell_phone }}" class="hover:underline">{{ $memberRole->member->cell_phone }}</a>
                                @else
                                    —
                                @endif
                            </td>
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
    </section>

    @if ($organization->canCreateChildren())
        @foreach ($groups as $group)
            @php
                $isLigue = $group->value === 'ligue';
                $children = $organization->children->where('group', $group);
                $badgeColor = $isLigue ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700';
                $avatarColor = $isLigue ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700';
            @endphp

            <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $badgeColor }}">
                            @if ($isLigue)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 003-3v-1.5m-9 4.5a3 3 0 01-3-3v-1.5m9-9v-3.375c0-.621-.504-1.125-1.125-1.125h-5.25c-.621 0-1.125.504-1.125 1.125V6m8.25 0a2.25 2.25 0 012.25 2.25v1.5a2.25 2.25 0 01-2.25 2.25M6.75 6a2.25 2.25 0 00-2.25 2.25v1.5a2.25 2.25 0 002.25 2.25m0-6v6.75m0 0a6.75 6.75 0 006.75 6.75 6.75 6.75 0 006.75-6.75m-13.5 0h13.5" />
                                </svg>
                            @else
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                </svg>
                            @endif
                        </span>
                        <h2 class="text-lg font-semibold">
                            {{ $group->pluralLabel() }} enfant
                            <span class="ml-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                {{ $children->count() }}
                            </span>
                        </h2>
                    </div>
                    <a href="{{ route('organizations.create', ['group' => $group->value]) }}"
                        class="inline-flex shrink-0 items-center gap-1 rounded-md bg-gray-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-black">
                        + Ajouter
                    </a>
                </div>

                @if ($children->isNotEmpty())
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($children as $child)
                            <div class="min-w-0 flex items-start justify-between gap-3 rounded-lg border border-gray-200 p-3">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $avatarColor }} text-sm font-semibold">
                                        {{ mb_substr($child->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-gray-900">{{ $child->name }}</p>
                                        <p class="truncate text-sm text-gray-500">{{ $child->responsable_name }}</p>
                                        <a href="mailto:{{ $child->responsable_email }}" class="truncate text-sm text-gray-500 hover:underline">{{ $child->responsable_email }}</a>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('organizations.destroy', $child) }}"
                                    onsubmit="return confirm('Retirer cette organisation ?');" class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">Retirer</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-lg border border-dashed border-gray-200 p-4 text-center text-sm text-gray-500">
                        Aucune {{ mb_strtolower($group->label()) }} de niveau {{ $organization->level->childLevel()->label() }} pour l'instant.
                    </p>
                @endif
            </section>
        @endforeach
    @endif
@endsection
