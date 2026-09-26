@extends('layouts.app')

@section('title', 'Propriétés')

@php
    $initials = fn (string $name) => collect(preg_split('/[\s-]+/', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
    $inputClass = 'mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none';
@endphp

@section('content')
    <p class="mb-2">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:underline">← Administration</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">Propriétés</h1>

    <div class="grid items-start gap-6 lg:grid-cols-[20rem_1fr]">
        {{-- My account: name and password, each edited in a panel that unfolds. --}}
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-xs font-semibold uppercase tracking-wide text-gray-500">Mon compte</h2>

            <div class="flex flex-col items-center text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-900 text-xl font-semibold text-white">
                    {{ $initials($admin->name) }}
                </span>
                <p class="mt-3 text-lg font-semibold text-gray-900">{{ $admin->name }}</p>
                <p class="text-sm text-gray-500">{{ $admin->email }}</p>
            </div>

            <div class="mt-6 space-y-3">
                <details class="group rounded-md border border-gray-200" @if ($errors->has('name')) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <span>✎ Modifier mon nom</span>
                        <span class="text-gray-400 transition group-open:rotate-180">▾</span>
                    </summary>
                    <form method="POST" action="{{ route('admin.properties.name') }}" class="space-y-3 border-t border-gray-200 p-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="name" class="block text-sm font-medium">Nom de l'administrateur</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $admin->name) }}" required class="{{ $inputClass }}">
                            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                            Enregistrer
                        </button>
                    </form>
                </details>

                <details class="group rounded-md border border-gray-200" @if ($errors->hasAny(['current_password', 'password'])) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <span>🔒 Changer mon mot de passe</span>
                        <span class="text-gray-400 transition group-open:rotate-180">▾</span>
                    </summary>
                    <form method="POST" action="{{ route('admin.properties.password') }}" class="space-y-3 border-t border-gray-200 p-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="current_password" class="block text-sm font-medium">Mot de passe actuel</label>
                            <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="{{ $inputClass }}">
                            @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium">Nouveau mot de passe</label>
                            <input id="password" type="password" name="password" required autocomplete="new-password" class="{{ $inputClass }}">
                            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium">Confirmation</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $inputClass }}">
                        </div>
                        <button type="submit" class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                            Changer le mot de passe
                        </button>
                    </form>
                </details>
            </div>
        </section>

        {{-- Every admin; adding one happens in a dialog. --}}
        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Administrateurs <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-gray-600">{{ $admins->count() }}</span>
                </h2>
                <button type="button" data-open-dialog="add-admin-dialog"
                    class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-black">
                    + Ajouter
                </button>
            </div>

            <ul class="divide-y divide-gray-100">
                @foreach ($admins as $other)
                    <li class="flex items-center gap-4 px-6 py-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $other->is($admin) ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-700' }} text-sm font-semibold">
                            {{ $initials($other->name) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900">
                                {{ $other->name }}
                                @if ($other->is($admin))
                                    <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-normal text-gray-500">vous</span>
                                @endif
                            </p>
                            <p class="truncate text-sm text-gray-500">{{ $other->email }}</p>
                        </div>
                        @unless ($other->is($admin))
                            <form method="POST" action="{{ route('admin.properties.admins.destroy', $other) }}"
                                onsubmit="return confirm('Retirer {{ addslashes($other->name) }} des administrateurs ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Retirer" aria-label="Retirer {{ $other->name }}"
                                    class="rounded p-2 text-gray-400 hover:bg-red-50 hover:text-red-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <dialog id="add-admin-dialog" class="w-full max-w-md rounded-lg p-0 shadow-xl backdrop:bg-black/50"
        @if ($errors->hasAny(['new_admin_name', 'new_admin_email', 'new_admin_password'])) data-open-on-load @endif>
        <form method="POST" action="{{ route('admin.properties.admins.store') }}" class="space-y-4 p-6">
            @csrf
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Ajouter un administrateur</h2>
                <button type="button" data-close-dialog aria-label="Fermer" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700">✕</button>
            </div>

            <div>
                <label for="new_admin_name" class="block text-sm font-medium">Nom de l'administrateur</label>
                <input id="new_admin_name" type="text" name="new_admin_name" value="{{ old('new_admin_name') }}" required class="{{ $inputClass }}">
                @error('new_admin_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="new_admin_email" class="block text-sm font-medium">Courriel</label>
                <input id="new_admin_email" type="email" name="new_admin_email" value="{{ old('new_admin_email') }}" required class="{{ $inputClass }}">
                @error('new_admin_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="new_admin_password" class="block text-sm font-medium">Mot de passe</label>
                    <input id="new_admin_password" type="password" name="new_admin_password" required autocomplete="new-password" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="new_admin_password_confirmation" class="block text-sm font-medium">Confirmation</label>
                    <input id="new_admin_password_confirmation" type="password" name="new_admin_password_confirmation" required autocomplete="new-password" class="{{ $inputClass }}">
                </div>
            </div>
            @error('new_admin_password')<p class="-mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" data-close-dialog
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                    Ajouter
                </button>
            </div>
        </form>
    </dialog>

    <script>
        const addAdminDialog = document.getElementById('add-admin-dialog');

        document.querySelector('[data-open-dialog]').addEventListener('click', () => addAdminDialog.showModal());
        addAdminDialog.querySelectorAll('[data-close-dialog]').forEach((button) => button.addEventListener('click', () => addAdminDialog.close()));
        addAdminDialog.addEventListener('click', (event) => {
            if (event.target === addAdminDialog) {
                addAdminDialog.close();
            }
        });

        // Reopen the dialog when the last attempt came back with errors.
        if (addAdminDialog.hasAttribute('data-open-on-load')) {
            addAdminDialog.showModal();
        }
    </script>
@endsection
