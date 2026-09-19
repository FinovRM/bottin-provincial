@extends('layouts.app')

@section('title', 'Valider la modification')

@section('content')
    <div class="mx-auto max-w-lg rounded-lg border border-gray-200 bg-white p-6">
        <h1 class="mb-4 text-xl font-semibold">Valider la modification</h1>

        <p class="mb-6 text-sm text-gray-700">
            Un nouveau responsable sera dorénavant en vigueur.
        </p>

        <dl class="mb-6 space-y-1 text-sm text-gray-700">
            <div>
                <dt class="inline font-medium">Nom :</dt>
                <dd class="inline">{{ $pending['responsable_name'] }}</dd>
            </div>
            <div>
                <dt class="inline font-medium">Courriel :</dt>
                <dd class="inline">{{ $pending['responsable_email'] }}</dd>
            </div>
            <div>
                <dt class="inline font-medium">Cellulaire :</dt>
                <dd class="inline">{{ \App\Support\CellPhone::format($pending['responsable_cell_phone']) }}</dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('responsable.update') }}">
            @csrf
            <input type="hidden" name="confirmed_change" value="1">
            <input type="hidden" name="responsable_name" value="{{ $pending['responsable_name'] }}">
            <input type="hidden" name="responsable_email" value="{{ $pending['responsable_email'] }}">
            <input type="hidden" name="responsable_cell_phone" value="{{ $pending['responsable_cell_phone'] }}">

            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                Autoriser la modification
            </button>
        </form>
    </div>
@endsection
