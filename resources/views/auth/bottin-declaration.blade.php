@extends('layouts.app')

@section('title', 'Déclaration du visiteur')

@section('content')
    <div class="mx-auto max-w-lg rounded-lg border border-gray-200 bg-white p-6">
        <h1 class="mb-4 text-xl font-semibold">Déclaration du visiteur</h1>

        <ul class="mb-6 list-disc space-y-2 pl-5 text-sm text-gray-700">
            <li>
                Les coordonnées qui me concernent ne sont pas confidentielles et j'accepte qu'elles soient inscrites
                dans ce bottin.
            </li>
            <li>
                Je m'engage à faire usage des données contenues dans ce bottin strictement dans le cadre des
                activités de mon organisation.
            </li>
        </ul>

        <form method="POST" action="{{ url()->full() }}">
            @csrf

            <div class="mb-2">
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="confirmed" value="1" required class="mt-0.5">
                    <span>Je confirme</span>
                </label>
                @error('confirmed')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="mt-4 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Accéder au bottin
            </button>
        </form>
    </div>
@endsection
