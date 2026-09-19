<form method="GET" action="{{ $action }}">
    @if ($query !== '')
        <input type="hidden" name="q" value="{{ $query }}">
    @endif

    @if ($showMyDirectionFilter ?? false)
        <div class="mb-4">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="my_direction" value="1" onchange="this.form.submit()"
                    @checked($myDirection ?? false)>
                {{ $myDirectionLabel ?? 'Ma direction' }}
            </label>
        </div>
    @endif

    @if ($showRegionFilter)
        <div class="mb-4">
            <label for="filter_region_id" class="block text-sm font-medium">Région</label>
            <select id="filter_region_id" name="region_id" onchange="this.form.submit()"
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Toutes</option>
                @foreach ($regions as $region)
                    <option value="{{ $region->id }}" @selected($regionId === (string) $region->id)>
                        {{ $region->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($showLocalFilter)
        <div class="mb-6">
            <label for="filter_local_id" class="block text-sm font-medium">Local</label>
            <select id="filter_local_id" name="local_id" onchange="this.form.submit()"
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($locals as $local)
                    <option value="{{ $local->id }}" @selected($localId === (string) $local->id)>
                        {{ $local->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <label for="filter_role" class="block text-sm font-medium">Fonction</label>
        <select id="filter_role" name="role" onchange="this.form.submit()"
            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">Toutes</option>
            @foreach ($roles as $roleOption)
                <option value="{{ $roleOption }}" @selected($role === $roleOption)>{{ $roleOption }}</option>
            @endforeach
        </select>
    </div>

    @if (($personalFilters ?? collect())->isNotEmpty())
        <hr class="mt-6 border-gray-200">

        <div class="mt-6">
            <label for="filter_personal_filter_id" class="block text-sm font-medium">Filtres personnels</label>
            <select id="filter_personal_filter_id" name="personal_filter_id" onchange="this.form.submit()"
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Aucun</option>
                @foreach ($personalFilters as $personalFilter)
                    <option value="{{ $personalFilter->id }}" @selected(($personalFilterId ?? '') === (string) $personalFilter->id)>
                        {{ $personalFilter->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <noscript>
        <button type="submit" class="mt-4 w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Filtrer
        </button>
    </noscript>
</form>
