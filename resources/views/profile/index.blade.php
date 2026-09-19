@extends('layouts.app')

@section('title', 'Mes propriétés')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Mes propriétés</h1>

    <section class="mb-8 rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Mes coordonnées</h2>

        <dl class="space-y-1 text-sm text-gray-700">
            <div>
                <dt class="inline font-medium">Nom :</dt>
                <dd class="inline">{{ $coordinates['name'] }}</dd>
            </div>
            <div>
                <dt class="inline font-medium">Rôle :</dt>
                <dd class="inline">{{ $coordinates['role'] }}</dd>
            </div>
            <div>
                <dt class="inline font-medium">Organisation :</dt>
                <dd class="inline">{{ $coordinates['organization'] }}</dd>
            </div>
            <div>
                <dt class="inline font-medium">Courriel :</dt>
                <dd class="inline">{{ $coordinates['email'] }}</dd>
            </div>
            <div>
                <dt class="inline font-medium">Cellulaire :</dt>
                <dd class="inline">{{ $coordinates['cell_phone'] ?: '—' }}</dd>
            </div>
        </dl>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Mes filtres personnels</h2>

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
                                <span class="text-sm text-gray-400">Modifier</span>
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
            })();
        </script>
    </section>
@endsection
