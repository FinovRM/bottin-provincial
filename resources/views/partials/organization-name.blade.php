{{-- An organization's name: a link opening its members when the visitor may look at them, plain text otherwise; then its last validation, marked when overdue. --}}
@if (in_array($organization->id, $viewableOrganizationIds, true))
    <button type="button" data-members-url="{{ route('bottin.organization-members', $organization) }}"
        class="inline-block max-w-full truncate align-bottom text-left underline decoration-gray-300 underline-offset-2 hover:decoration-gray-900">{{ $organization->name }}</button>
@else
    {{ $organization->name }}
@endif
@php($isValid = \App\Support\BottinValidation::isValid($organization))
<span @class(['whitespace-nowrap text-xs', 'text-gray-400' => $isValid, 'rounded bg-red-100 px-1 font-medium text-red-700' => ! $isValid])
    @unless ($isValid) title="Bottin non validé" @endunless>({{ $organization->validated_at ? $organization->validated_at->timezone(\App\Support\BottinValidation::TIMEZONE)->translatedFormat('j M Y') : 'jamais validé' }}{{ $isValid ? '' : ' — non validé' }})</span>
