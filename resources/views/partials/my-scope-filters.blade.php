{{-- "Mon parent" / "Niveau régional" / "Mon organisation": each replaces the usual scope; checked together, they add up. --}}
@if ($showMyDirectionFilter ?? false)
    <div class="mb-4">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" name="my_direction" value="1" onchange="this.form.submit()"
                @checked($myDirection ?? false)>
            {{ $myDirectionLabel ?? 'Ma direction' }}
        </label>
    </div>
@endif

@if ($showRegionalLevelFilter ?? false)
    <div class="mb-4">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" name="regional_level" value="1" onchange="this.form.submit()"
                @checked($regionalLevel ?? false)>
            Niveau régional
        </label>
    </div>
@endif

@if ($showMyOrganizationFilter ?? false)
    <div class="mb-4">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" name="my_organization" value="1" onchange="this.form.submit()"
                @checked($myOrganization ?? false)>
            Mon organisation
        </label>
    </div>
@endif
