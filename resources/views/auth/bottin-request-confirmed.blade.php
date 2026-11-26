@extends('layouts.app')

@section('title', 'Modification confirmée')

@section('content')
    <div class="mx-auto max-w-lg rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">Modification confirmée</h1>
        <p class="mt-2 text-sm text-gray-600">
            Vos coordonnées ont été mises à jour pour les rôles suivants. Vous pouvez maintenant vous connecter au bottin avec
            <span class="font-medium">{{ $bottinRequest->new_email }}</span>.
        </p>
        <ul class="mt-4 list-disc pl-5 text-sm text-gray-800">
            @foreach ($bottinRequest->roles as $role)
                <li>{{ $role['role'] }} — {{ $role['organization'] }}</li>
            @endforeach
        </ul>
        <a href="{{ route('login') }}" class="mt-6 inline-block rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">Se connecter</a>
    </div>
@endsection
