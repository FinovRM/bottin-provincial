{{-- Minimum and allowed roles for one group of children, right after that group's "enfant" section on the properties page. --}}
@php
    $groupKey = $group->value;
    $roleTables = [
        [
            'heading' => 'Rôles minimum',
            'roles' => $minimumRoles,
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
            'roles' => $allowedRoles,
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

<section class="mb-8 rounded-lg border {{ $sectionBorder }} bg-white p-5">
    <div class="mb-1 flex items-center gap-3">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $badgeColor }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 4.556-3.04 8.55-7.5 9.75C9.04 20.55 6 16.556 6 12V6.545c0-.55.37-1.03.9-1.166l5.25-1.35a1.5 1.5 0 01.7 0l5.25 1.35c.53.136.9.617.9 1.166V12z" />
            </svg>
        </span>
        <h2 class="text-lg font-semibold">Rôles de mes {{ mb_strtolower($group->pluralLabel()) }} enfant</h2>
    </div>
    <p class="mb-4 text-sm text-gray-500">
        Rôles utilisables par chacune de vos {{ mb_strtolower($group->pluralLabel()) }} {{ $organization->level->childLevel()->pluralLabel() }} :
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
            @php $reopened = session('reopen_dialog') === $table['addDialog']; @endphp
            <h3 class="mb-4 text-lg font-semibold">{{ $table['addTitle'] }}</h3>
            @if ($reopened)
                <p class="mb-4 rounded-md bg-green-50 px-3 py-2 text-sm text-green-800">« {{ session('added_role') }} » ajouté. Ajoutez-en un autre ou terminez.</p>
            @endif

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
                        Terminer
                    </button>
                    <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                        Ajouter
                    </button>
                </div>
            </form>
        </dialog>

        @if ($errors->{$table['addBag']}->any() || session('reopen_dialog') === $table['addDialog'])
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
