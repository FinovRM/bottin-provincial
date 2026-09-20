@extends('layouts.app')

@section('title', 'Déclaration du visiteur')

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 4.556-3.04 8.55-7.5 9.75C9.04 20.55 6 16.556 6 12V6.545c0-.55.37-1.03.9-1.166l5.25-1.35a1.5 1.5 0 01.7 0l5.25 1.35c.53.136.9.617.9 1.166V12z" />
                </svg>
            </div>

            <h1 class="mt-4 text-2xl font-semibold text-gray-900">Déclaration du visiteur</h1>
            <p class="mt-1 text-sm text-gray-500">Avant d'accéder au bottin, veuillez confirmer les énoncés suivants.</p>

            <ul class="mt-6 space-y-3">
                <li class="flex gap-3 rounded-md bg-gray-50 p-4">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-base text-gray-800">
                        Les coordonnées qui me concernent ne sont pas confidentielles et j'accepte qu'elles soient
                        inscrites dans ce bottin.
                    </span>
                </li>
                <li class="flex gap-3 rounded-md bg-gray-50 p-4">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-base text-gray-800">
                        Je m'engage à faire usage des données contenues dans ce bottin strictement dans le cadre des
                        activités de mon organisation.
                    </span>
                </li>
            </ul>

            <form method="POST" action="{{ url()->full() }}" class="mt-6 border-t border-gray-100 pt-6">
                @csrf

                <label class="flex items-start gap-3 rounded-md border border-gray-200 p-4 text-sm font-medium text-gray-900 has-[:checked]:border-gray-900 has-[:checked]:bg-gray-50">
                    <input type="checkbox" name="confirmed" value="1" required class="mt-0.5 h-4 w-4">
                    <span>Je confirme avoir lu et j'accepte les énoncés ci-dessus.</span>
                </label>
                @error('confirmed')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

                <button type="submit" class="mt-4 w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black sm:w-auto">
                    Accéder au bottin
                </button>
            </form>
        </div>
    </div>
@endsection
