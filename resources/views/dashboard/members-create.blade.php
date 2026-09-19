@extends('layouts.app')

@section('title', 'Ajouter un membre')

@section('content')
    <p class="mb-2">
        <a href="{{ route('dashboard.properties') }}" class="text-sm text-gray-500 hover:underline">← Mes propriétés</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">Ajouter un membre</h1>

    <form method="POST" action="{{ route('members.store') }}" class="max-w-sm space-y-4">
        @csrf

        <div>
            <label for="member_role" class="block text-sm font-medium">Rôle</label>
            <input id="member_role" type="text" name="role" value="{{ old('role') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="member_name" class="block text-sm font-medium">Nom</label>
            <input id="member_name" type="text" name="name" value="{{ old('name') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="member_email" class="block text-sm font-medium">Courriel</label>
            <input id="member_email" type="email" name="email" value="{{ old('email') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="member_email_confirmation" class="block text-sm font-medium">Validation du courriel</label>
            <input id="member_email_confirmation" type="email" name="email_confirmation"
                value="{{ old('email_confirmation') }}" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="member_cell_phone" class="block text-sm font-medium">Cellulaire</label>
            <input id="member_cell_phone" type="text" name="cell_phone" value="{{ old('cell_phone') }}"
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
            Ajouter
        </button>
    </form>
@endsection
