@extends('layouts.app')

@section('title', 'Rôles multiples')

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">Rôles multiples</h1>
    <p class="mb-6 text-sm text-gray-600">
        Plusieurs rôles sont associés à cette adresse courriel. Choisissez celui que vous souhaitez utiliser.
    </p>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($identities as $option)
            <form method="POST" action="{{ url()->full() }}">
                @csrf
                <input type="hidden" name="role_choice" value="{{ $option['key'] }}">
                <button type="submit"
                    class="w-full rounded-lg border border-gray-200 bg-white p-5 text-left hover:border-gray-400">
                    <h2 class="font-semibold">{{ $option['organization'] }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $option['role'] }}</p>
                </button>
            </form>
        @endforeach
    </div>
@endsection
