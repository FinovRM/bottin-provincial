@extends('layouts.app')

@section('title', 'Propriétés')

@section('content')
    <p class="mb-2">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:underline">← Administration</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">Propriétés</h1>

    <div class="grid gap-6 md:grid-cols-2">
        <section class="rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-semibold">Mes informations</h2>

            <form method="POST" action="{{ route('admin.properties.name') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium">Nom de l'administrateur</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $admin->name) }}" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <p class="block text-sm font-medium">Courriel</p>
                    <p class="mt-1 text-sm text-gray-600">{{ $admin->email }}</p>
                </div>

                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                    Enregistrer
                </button>
            </form>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-semibold">Changer mon mot de passe</h2>

            <form method="POST" action="{{ route('admin.properties.password') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-medium">Mot de passe actuel</label>
                    <input id="current_password" type="password" name="current_password" required autocomplete="current-password"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium">Nouveau mot de passe</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium">Confirmation du nouveau mot de passe</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>

                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                    Changer le mot de passe
                </button>
            </form>
        </section>
    </div>

    <section class="mt-6 rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Administrateurs</h2>

        <table class="mb-6 w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-2">Nom</th>
                    <th class="px-4 py-2">Courriel</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($admins as $other)
                    <tr class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-2 font-medium">{{ $other->name }}@if ($other->is($admin)) <span class="text-gray-400">(vous)</span>@endif</td>
                        <td class="px-4 py-2">{{ $other->email }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h3 class="mb-4 font-semibold">Ajouter un administrateur</h3>

        <form method="POST" action="{{ route('admin.properties.admins.store') }}" class="max-w-sm space-y-4">
            @csrf

            <div>
                <label for="new_admin_name" class="block text-sm font-medium">Nom de l'administrateur</label>
                <input id="new_admin_name" type="text" name="new_admin_name" value="{{ old('new_admin_name') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('new_admin_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="new_admin_email" class="block text-sm font-medium">Courriel</label>
                <input id="new_admin_email" type="email" name="new_admin_email" value="{{ old('new_admin_email') }}" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('new_admin_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="new_admin_password" class="block text-sm font-medium">Mot de passe</label>
                <input id="new_admin_password" type="password" name="new_admin_password" required autocomplete="new-password"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @error('new_admin_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="new_admin_password_confirmation" class="block text-sm font-medium">Confirmation du mot de passe</label>
                <input id="new_admin_password_confirmation" type="password" name="new_admin_password_confirmation" required autocomplete="new-password"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>

            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Ajouter
            </button>
        </form>
    </section>
@endsection
