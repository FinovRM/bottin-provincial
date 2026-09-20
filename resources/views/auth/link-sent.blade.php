@extends('layouts.app')

@section('title', 'Lien envoyé')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                <svg class="h-7 w-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>

            <h1 class="mt-4 text-2xl font-semibold text-gray-900">Vérifiez vos courriels</h1>

            <p class="mt-3 text-base text-gray-700">
                Un lien de connexion vous a été envoyé.<br>
                Il est valide pour <span class="font-semibold text-gray-900">15 minutes</span>.
            </p>

            <p class="mt-6 text-sm text-gray-500">
                Vous ne le voyez pas ? Vérifiez vos courriels indésirables, ou
                <a href="{{ route('login') }}" class="font-medium text-gray-700 hover:underline">réessayez</a>.
            </p>
        </div>
    </div>
@endsection
