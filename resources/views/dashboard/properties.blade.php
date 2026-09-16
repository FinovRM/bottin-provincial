@extends('layouts.app')

@section('title', 'Mes propriétés')

@section('content')
    <p class="mb-2">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:underline">← Mon tableau de bord</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">{{ $organization->name }}</h1>

    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Ma fiche ({{ $organization->level->label() }})</h2>

        <form method="POST" action="{{ route('organizations.update', $organization) }}" class="max-w-sm space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium">Nom de l'organisation</label>
                <input id="name" type="text" name="name" value="{{ old('name', $organization->name) }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

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

            <div class="border-t border-gray-100 pt-4">
                <p class="mb-3 text-sm font-medium text-gray-700">Informations de l'organisation</p>

                <div class="space-y-4">
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
            </div>

            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Enregistrer
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

    <section class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Membres de {{ $organization->name }}</h2>
        <p class="mb-4 text-xs text-gray-500">
            Une personne (un courriel) peut occuper plusieurs rôles ; son nom et son cellulaire ne se saisissent
            qu'une fois et restent ensuite fixes. Chaque rôle s'ajoute, se modifie ou se retire séparément.
        </p>

        @forelse ($organization->memberRoles as $memberRole)
            <div class="mb-3 flex flex-col gap-2 border-b border-gray-100 pb-3 last:border-0 sm:flex-row sm:items-end">
                <form method="POST" action="{{ route('members.update', $memberRole) }}" class="flex-1">
                    @csrf
                    @method('PUT')

                    <label class="block text-xs font-medium text-gray-500">Fonction</label>
                    <input type="text" name="role" value="{{ old('role', $memberRole->role) }}" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                    <button type="submit" class="mt-1 text-xs text-gray-700 hover:underline">Enregistrer</button>
                </form>
                <div class="flex-1 text-sm">
                    <p class="text-xs font-medium text-gray-500">Nom</p>
                    <p>{{ $memberRole->member->name }}</p>
                </div>
                <div class="flex-1 text-sm">
                    <p class="text-xs font-medium text-gray-500">Courriel</p>
                    <p>{{ $memberRole->member->email }}</p>
                </div>
                <div class="flex-1 text-sm">
                    <p class="text-xs font-medium text-gray-500">Cellulaire</p>
                    <p>{{ $memberRole->member->cell_phone ?: '—' }}</p>
                </div>
                <form method="POST" action="{{ route('members.destroy', $memberRole) }}"
                    onsubmit="return confirm('Retirer ce rôle ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:underline">Retirer</button>
                </form>
            </div>
        @empty
            <p class="text-sm text-gray-500">Aucun membre pour l'instant.</p>
        @endforelse

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
@endsection
