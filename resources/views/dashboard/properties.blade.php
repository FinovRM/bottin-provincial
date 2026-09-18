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

    <form method="POST" action="{{ route('organizations.update', $organization) }}">
        @csrf
        @method('PUT')

        <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-semibold">Responsable du bottin</h2>

            <div class="max-w-sm space-y-4">
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
            </div>
        </section>

        <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-semibold">Coordonnées de l'organisation ({{ $organization->level->label() }})</h2>

            <div class="max-w-sm space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium">Nom de l'organisation</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $organization->name) }}" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium">Adresse</label>
                    <input id="address" type="text" name="address" value="{{ old('address', $organization->address) }}"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="business_number" class="block text-sm font-medium">Numéro d'entreprise</label>
                    <input id="business_number" type="text" name="business_number"
                        value="{{ old('business_number', $organization->business_number) }}"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    @error('business_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="website" class="block text-sm font-medium">Site web</label>
                    <input id="website" type="url" name="website" placeholder="https://…"
                        value="{{ old('website', $organization->website) }}"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    @error('website')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <p class="mb-8">
            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Enregistrer
            </button>
        </p>
    </form>

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

        <h3 class="mt-6 mb-3 text-sm font-semibold">Ajouter un rôle</h3>

        <form method="POST" action="{{ route('members.store') }}" class="max-w-sm space-y-4">
            @csrf

            <div>
                <label for="member_role" class="block text-sm font-medium">Fonction</label>
                <input id="member_role" type="text" name="role" value="{{ old('role') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="member_email" class="block text-sm font-medium">Courriel</label>
                <input id="member_email" type="email" name="email" value="{{ old('email') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="member_name" class="block text-sm font-medium">Nom</label>
                <input id="member_name" type="text" name="name" value="{{ old('name') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">Ignoré si ce courriel est déjà enregistré.</p>
            </div>
            <div>
                <label for="member_cell_phone" class="block text-sm font-medium">Cellulaire</label>
                <input id="member_cell_phone" type="text" name="cell_phone" value="{{ old('cell_phone') }}"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">Ignoré si ce courriel est déjà enregistré.</p>
            </div>

            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Ajouter
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
