{{-- "Mon parent" / "Mon organisation": each replaces the usual scope with that organization. --}}
@if ($showMyDirectionFilter ?? false)
    <div class="mb-4">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" name="my_direction" value="1" onchange="this.form.submit()"
                @checked($myDirection ?? false)>
            {{ $myDirectionLabel ?? 'Ma direction' }}
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
