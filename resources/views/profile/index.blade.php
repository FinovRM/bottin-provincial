@extends('layouts.app')

@section('title', 'Mes propriétés')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Mes propriétés</h1>

    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
        <div class="mb-4 flex items-center gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </span>
            <h2 class="text-lg font-semibold">Mes coordonnées</h2>
        </div>

        <dl class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-1 text-sm text-gray-700">
            <dt class="text-right font-medium">Nom :</dt>
            <dd>{{ $coordinates['name'] }}</dd>

            <dt class="text-right font-medium">Rôle :</dt>
            <dd>{{ $coordinates['role'] }}</dd>

            <dt class="text-right font-medium">Organisation :</dt>
            <dd>{{ $coordinates['organization'] }}</dd>

            <dt class="text-right font-medium">Courriel :</dt>
            <dd>{{ $coordinates['email'] }}</dd>

            <dt class="text-right font-medium">Cellulaire :</dt>
            <dd>{{ $coordinates['cell_phone'] ?: '—' }}</dd>
        </dl>
    </section>

    @if (auth('web')->check() && auth('web')->user()->canCreateChildren())
        @foreach ($rolesByGroup as $groupKey => $groupData)
            @php
                $group = $groupData['group'];
                $roleTables = [
                    [
                        'heading' => 'Rôles minimum',
                        'roles' => $groupData['minimumRoles'],
                        'bagPrefix' => 'minimum-role',
                        'addDialog' => "add-minimum-role-{$groupKey}-dialog",
                        'addBag' => "minimum-role-add-{$groupKey}",
                        'addRoute' => route('minimum-roles.store'),
                        'updateRoute' => fn ($role) => route('minimum-roles.update', $role),
                        'destroyRoute' => fn ($role) => route('minimum-roles.destroy', $role),
                        'empty' => 'Aucun rôle minimum défini.',
                        'editTitle' => 'Modifier le rôle minimum',
                        'deleteTitle' => 'Supprimer le rôle minimum',
                        'addTitle' => 'Ajouter un rôle minimum',
                    ],
                    [
                        'heading' => 'Rôles permis',
                        'roles' => $groupData['allowedRoles'],
                        'bagPrefix' => 'allowed-role',
                        'addDialog' => "add-allowed-role-{$groupKey}-dialog",
                        'addBag' => "allowed-role-add-{$groupKey}",
                        'addRoute' => route('allowed-roles.store'),
                        'updateRoute' => fn ($role) => route('allowed-roles.update', $role),
                        'destroyRoute' => fn ($role) => route('allowed-roles.destroy', $role),
                        'empty' => 'Aucun rôle permis défini.',
                        'editTitle' => 'Modifier le rôle permis',
                        'deleteTitle' => 'Supprimer le rôle permis',
                        'addTitle' => 'Ajouter un rôle permis',
                    ],
                ];
            @endphp

            <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
                <div class="mb-1 flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 4.556-3.04 8.55-7.5 9.75C9.04 20.55 6 16.556 6 12V6.545c0-.55.37-1.03.9-1.166l5.25-1.35a1.5 1.5 0 01.7 0l5.25 1.35c.53.136.9.617.9 1.166V12z" />
                        </svg>
                    </span>
                    <h2 class="text-lg font-semibold">Rôles de mes {{ mb_strtolower($group->pluralLabel()) }} enfant</h2>
                </div>
                <p class="mb-4 text-sm text-gray-500">
                    Rôles utilisables par chacune de vos {{ mb_strtolower($group->pluralLabel()) }} {{ auth('web')->user()->level->childLevel()->pluralLabel() }} :
                    les rôles minimum sont exigés, les rôles permis sont simplement autorisés en plus.
                </p>

                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach ($roleTables as $table)
                        <div class="min-w-0">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-gray-900">{{ $table['heading'] }}</h3>
                                <button type="button" onclick="document.getElementById('{{ $table['addDialog'] }}').showModal()"
                                    class="shrink-0 text-sm text-gray-700 hover:underline">
                                    + Ajouter
                                </button>
                            </div>

                            <div class="overflow-x-auto rounded-lg border border-gray-200">
                                <table class="w-full text-left text-sm">
                                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                                        <tr>
                                            <th class="px-4 py-2">Nom du rôle</th>
                                            <th class="px-4 py-2 text-center">Membres</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($table['roles'] as $role)
                                            @php $bag = "{$table['bagPrefix']}-{$role->id}"; @endphp
                                            <tr class="border-b border-gray-100 last:border-0">
                                                <td class="px-4 py-2">{{ $role->name }}</td>
                                                <td class="px-4 py-2 text-center">{{ $role->member_count }}</td>
                                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                                    <button type="button" onclick="document.getElementById('edit-{{ $bag }}').showModal()"
                                                        class="text-sm text-gray-700 hover:underline">
                                                        Modifier
                                                    </button>
                                                    <button type="button" onclick="document.getElementById('delete-{{ $bag }}').showModal()"
                                                        class="ml-3 text-sm text-red-600 hover:underline">
                                                        Supprimer
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-4 py-6 text-center text-gray-500">{{ $table['empty'] }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                @foreach ($roleTables as $table)
                    <dialog id="{{ $table['addDialog'] }}" class="w-full max-w-sm rounded-lg border border-gray-200 p-6 backdrop:bg-black/30">
                        <h3 class="mb-4 text-lg font-semibold">{{ $table['addTitle'] }}</h3>

                        <form method="POST" action="{{ $table['addRoute'] }}">
                            @csrf
                            <input type="hidden" name="group" value="{{ $groupKey }}">
                            <div class="mb-6">
                                <label for="{{ $table['addDialog'] }}-name" class="block text-sm font-medium">Nom du rôle</label>
                                <input id="{{ $table['addDialog'] }}-name" type="text" name="name"
                                    value="{{ $errors->{$table['addBag']}->has('name') ? old('name') : '' }}" required
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                @error('name', $table['addBag'])<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex gap-3">
                                <button type="button" onclick="document.getElementById('{{ $table['addDialog'] }}').close()"
                                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Annuler
                                </button>
                                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                                    Ajouter
                                </button>
                            </div>
                        </form>
                    </dialog>

                    @if ($errors->{$table['addBag']}->any())
                        <script>document.getElementById('{{ $table['addDialog'] }}').showModal();</script>
                    @endif

                    @foreach ($table['roles'] as $role)
                        @php $bag = "{$table['bagPrefix']}-{$role->id}"; @endphp
                        <dialog id="edit-{{ $bag }}" class="w-full max-w-sm rounded-lg border border-gray-200 p-6 backdrop:bg-black/30">
                            <h3 class="mb-4 text-lg font-semibold">{{ $table['editTitle'] }}</h3>

                            <form method="POST" action="{{ $table['updateRoute']($role) }}">
                                @csrf
                                @method('PUT')

                                <div class="mb-4">
                                    <label for="{{ $bag }}-name" class="block text-sm font-medium">Nom du rôle</label>
                                    <input id="{{ $bag }}-name" type="text" name="name"
                                        value="{{ $errors->{$bag}->has('name') ? old('name') : $role->name }}" required
                                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    @error('name', $bag)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>

                                @if ($role->member_count > 0)
                                    <p class="mb-4 text-sm text-gray-500">
                                        {{ $role->member_count }} membre{{ $role->member_count > 1 ? 's' : '' }}
                                        actuellement affecté{{ $role->member_count > 1 ? 's' : '' }} à ce rôle
                                        {{ $role->member_count > 1 ? 'seront corrigés' : 'sera corrigé' }} avec le nouveau nom.
                                    </p>
                                @endif

                                <div class="flex gap-3">
                                    <button type="button" onclick="document.getElementById('edit-{{ $bag }}').close()"
                                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        Annuler
                                    </button>
                                    <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                                        Confirmer
                                    </button>
                                </div>
                            </form>
                        </dialog>

                        <dialog id="delete-{{ $bag }}" class="w-full max-w-sm rounded-lg border border-gray-200 p-6 backdrop:bg-black/30">
                            <h3 class="mb-4 text-lg font-semibold">{{ $table['deleteTitle'] }}</h3>

                            <p class="mb-6 text-sm text-gray-700">
                                @if ($role->member_count > 0)
                                    Suppression de tous les membres de l'organisation ayant ce rôle
                                    ({{ $role->member_count }} membre{{ $role->member_count > 1 ? 's' : '' }}).
                                    Cette action est irréversible.
                                @else
                                    Aucun membre n'est actuellement affecté à ce rôle.
                                @endif
                            </p>

                            <form method="POST" action="{{ $table['destroyRoute']($role) }}">
                                @csrf
                                @method('DELETE')
                                <div class="flex gap-3">
                                    <button type="button" onclick="document.getElementById('delete-{{ $bag }}').close()"
                                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        Annuler
                                    </button>
                                    <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                                        Supprimer
                                    </button>
                                </div>
                            </form>
                        </dialog>

                        @if ($errors->{$bag}->any())
                            <script>document.getElementById('edit-{{ $bag }}').showModal();</script>
                        @endif
                    @endforeach
                @endforeach
            </section>
        @endforeach
    @endif

    <section class="rounded-lg border border-gray-200 bg-white p-5">
        <div class="mb-1 flex items-center gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
            </span>
            <h2 class="text-lg font-semibold">Mes filtres personnels</h2>
        </div>
        <p class="mb-4 text-sm text-gray-500">
            Combinaisons de région, local et rôle enregistrées pour filtrer rapidement le bottin des membres.
        </p>

        <div class="mb-6 overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2">Nom du filtre</th>
                        <th class="px-4 py-2">Description</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($personalFilters as $personalFilter)
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="px-4 py-2 font-medium">{{ $personalFilter->name }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $personalFilter->description ?: '—' }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <button type="button" data-show-filter
                                    data-region-ids="{{ json_encode($personalFilter->region_ids ?? []) }}"
                                    data-local-ids="{{ json_encode($personalFilter->local_ids ?? []) }}"
                                    data-roles="{{ json_encode($personalFilter->roles ?? []) }}"
                                    class="text-sm text-gray-700 hover:underline">
                                    Montrer
                                </button>
                                <button type="submit" form="personal-filter-form"
                                    formaction="{{ route('profile.filters.update', $personalFilter) }}?_method=PUT"
                                    formmethod="post" formnovalidate
                                    class="ml-3 text-sm text-gray-700 hover:underline">
                                    Modifier
                                </button>
                                <form method="POST" action="{{ route('profile.filters.destroy', $personalFilter) }}"
                                    onsubmit="return confirm('Supprimer ce filtre ?');" class="ml-3 inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">Aucun filtre enregistré.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form id="personal-filter-form" method="POST" action="{{ route('profile.filters.store') }}">
            @csrf

            <div class="mb-6 flex gap-3">
                <button type="button" onclick="document.getElementById('add-filter-dialog').showModal()"
                    class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                    Ajouter un filtre
                </button>
                <button type="button" data-reset-filters
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Réinitialiser
                </button>
            </div>

            <div class="grid gap-6 sm:grid-cols-3">
                <div>
                    <p class="mb-2 text-sm font-medium">Régional</p>
                    <div class="flex flex-col gap-1" data-column="region">
                        @if ($regions->isNotEmpty())
                            <button type="button" data-select-all
                                class="cursor-pointer select-none rounded-md border border-gray-300 px-3 py-1 text-left text-sm hover:bg-gray-50">
                                Tous
                            </button>
                        @endif
                        @forelse ($regions as $region)
                            <label class="cursor-pointer select-none rounded-md border border-gray-300 px-3 py-1 text-sm has-[:checked]:border-gray-900 has-[:checked]:bg-gray-900 has-[:checked]:text-white">
                                <input type="checkbox" name="region_ids[]" value="{{ $region->id }}" class="hidden">
                                {{ $region->name }}
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">Aucune organisation régionale.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-sm font-medium">Local</p>
                    <div class="flex flex-col gap-1" data-column="local">
                        @if ($locals->isNotEmpty())
                            <button type="button" data-select-all
                                class="cursor-pointer select-none rounded-md border border-gray-300 px-3 py-1 text-left text-sm hover:bg-gray-50">
                                Tous
                            </button>
                        @endif
                        @forelse ($locals as $local)
                            <label data-region-id="{{ $local->parent_id }}"
                                class="cursor-pointer select-none rounded-md border border-gray-300 px-3 py-1 text-sm has-[:checked]:border-gray-900 has-[:checked]:bg-gray-900 has-[:checked]:text-white">
                                <input type="checkbox" name="local_ids[]" value="{{ $local->id }}" class="hidden">
                                {{ $local->name }}
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">Aucune organisation locale.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-sm font-medium">Rôle</p>
                    <div class="flex flex-col gap-1" data-column="role">
                        @if ($roles->isNotEmpty())
                            <button type="button" data-select-all
                                class="cursor-pointer select-none rounded-md border border-gray-300 px-3 py-1 text-left text-sm hover:bg-gray-50">
                                Tous
                            </button>
                        @endif
                        @forelse ($roles as $roleOption)
                            <label class="cursor-pointer select-none rounded-md border border-gray-300 px-3 py-1 text-sm has-[:checked]:border-gray-900 has-[:checked]:bg-gray-900 has-[:checked]:text-white">
                                <input type="checkbox" name="roles[]" value="{{ $roleOption }}" class="hidden">
                                {{ $roleOption }}
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">Aucun rôle.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </form>

        <dialog id="add-filter-dialog" class="w-full max-w-sm rounded-lg border border-gray-200 p-6 backdrop:bg-black/30">
            <h3 class="mb-4 text-lg font-semibold">Ajouter un filtre</h3>

            <div class="mb-4">
                <label for="filter_name" class="block text-sm font-medium">Nom du filtre</label>
                <input id="filter_name" type="text" name="name" form="personal-filter-form" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div class="mb-6">
                <label for="filter_description" class="block text-sm font-medium">Description</label>
                <textarea id="filter_description" name="description" form="personal-filter-form" rows="3"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('add-filter-dialog').close()"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit" form="personal-filter-form"
                    class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                    Ajouter
                </button>
            </div>
        </dialog>

        <script>
            (function () {
                const form = document.getElementById('personal-filter-form');
                const regionCheckboxes = form.querySelectorAll('input[name="region_ids[]"]');
                const localLabels = form.querySelectorAll('[data-region-id]');

                function applyRegionFilter() {
                    const checkedRegionIds = Array.from(regionCheckboxes)
                        .filter((checkbox) => checkbox.checked)
                        .map((checkbox) => checkbox.value);

                    localLabels.forEach((label) => {
                        label.hidden = checkedRegionIds.length > 0 && ! checkedRegionIds.includes(label.dataset.regionId);
                    });
                }

                regionCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', applyRegionFilter));

                form.querySelectorAll('[data-select-all]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const column = button.closest('[data-column]');
                        column.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                            if (! checkbox.closest('label').hidden) {
                                checkbox.checked = true;
                            }
                        });
                        applyRegionFilter();
                    });
                });

                form.querySelector('[data-reset-filters]').addEventListener('click', () => {
                    form.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => checkbox.checked = false);
                    localLabels.forEach((label) => label.hidden = false);
                });

                document.querySelectorAll('[data-show-filter]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const regionIds = JSON.parse(button.dataset.regionIds || '[]').map(String);
                        const localIds = JSON.parse(button.dataset.localIds || '[]').map(String);
                        const roles = JSON.parse(button.dataset.roles || '[]');

                        form.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => checkbox.checked = false);
                        localLabels.forEach((label) => label.hidden = false);

                        regionCheckboxes.forEach((checkbox) => {
                            checkbox.checked = regionIds.includes(checkbox.value);
                        });
                        applyRegionFilter();

                        form.querySelectorAll('input[name="local_ids[]"]').forEach((checkbox) => {
                            checkbox.checked = localIds.includes(checkbox.value);
                        });

                        form.querySelectorAll('input[name="roles[]"]').forEach((checkbox) => {
                            checkbox.checked = roles.includes(checkbox.value);
                        });

                        form.scrollIntoView({behavior: 'smooth', block: 'start'});
                    });
                });
            })();
        </script>
    </section>
@endsection
