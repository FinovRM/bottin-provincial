{{-- A member's phone, with its extension; the tel: link dials the extension after a pause. --}}
@props(['member', 'fallback' => null])
@if ($member->cell_phone)
    <a href="tel:{{ preg_replace('/\D/', '', $member->cell_phone) }}{{ $member->extension ? ','.$member->extension : '' }}" {{ $attributes->merge(['class' => 'hover:underline']) }}>{{ $member->cell_phone }}@if ($member->extension) poste {{ $member->extension }}@endif</a>
@else
    {{ $fallback }}
@endif
