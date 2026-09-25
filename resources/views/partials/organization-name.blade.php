{{-- An organization's name: a link opening its members when the visitor may look at them, plain text otherwise. --}}
@if (in_array($organization->id, $viewableOrganizationIds, true))
    <button type="button" data-members-url="{{ route('bottin.organization-members', $organization) }}"
        class="inline-block max-w-full truncate align-bottom text-left underline decoration-gray-300 underline-offset-2 hover:decoration-gray-900">{{ $organization->name }}</button>
@else
    {{ $organization->name }}
@endif
