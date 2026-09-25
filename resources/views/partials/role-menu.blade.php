{{-- Lets a visitor with several roles pick or change the one they're using; changing brings them back home. --}}
@php
    $roleChoices = \App\Support\VisitorIdentities::choicesForEmail(\App\Support\VisitorIdentities::currentEmail());
    $currentRoleKey = \App\Support\VisitorIdentities::currentKey();
@endphp

@if ($roleChoices->count() > 1)
    <form method="POST" action="{{ route('role.switch') }}">
        @csrf
        <label for="role-menu" class="sr-only">Rôle</label>
        <select id="role-menu" name="identity" onchange="this.form.submit()"
            class="max-w-64 rounded-md border border-gray-300 py-1 pl-2 pr-8 text-sm text-gray-700">
            @foreach ($roleChoices as $choice)
                <option value="{{ $choice['key'] }}" @selected($choice['key'] === $currentRoleKey)>
                    {{ $choice['role'] }}{{ $choice['organization'] ? ' — '.$choice['organization'] : '' }}
                </option>
            @endforeach
        </select>
        <noscript>
            <button type="submit" class="ml-1 text-gray-700 hover:underline">Changer</button>
        </noscript>
    </form>
@endif
