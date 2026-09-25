@extends('layouts.app')

@section('title', 'Importer des membres')

@section('content')
    <p class="mb-2">
        <a href="{{ route('admin.members.index') }}" class="text-sm text-gray-500 hover:underline">← Tous les membres</a>
    </p>
    <h1 class="mb-6 text-2xl font-semibold">Importer des membres (CSV)</h1>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 text-sm">
        <p class="mb-2 font-medium">Format attendu</p>
        <p class="mb-3 text-gray-600">
            Un fichier CSV avec une ligne d'en-tête, dans cet ordre exact :
        </p>
        <code class="block overflow-x-auto rounded bg-gray-50 p-3 text-xs">{{ implode(',', $columns) }}</code>
        <ul class="mt-3 list-disc space-y-1 pl-5 text-gray-600">
            <li><code>organization_responsable_email</code> : courriel du responsable de l'organisation à laquelle rattacher le rôle.</li>
            <li><code>extension</code> : poste téléphonique, facultatif. Si la colonne est vide et que <code>cell_phone</code> compte plus de 10 chiffres, les chiffres en trop deviennent le poste.</li>
            <li>Chaque ligne ajoute un rôle. Si l'adresse <code>email</code> existe déjà, le nom, le téléphone et le poste déjà en fiche sont conservés — seul le rôle s'ajoute pour cette organisation.</li>
        </ul>
    </div>

    @if (session('import_errors') && count(session('import_errors')))
        <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-800">
            <p class="mb-2 font-medium">Erreurs rencontrées :</p>
            <ul class="list-disc space-y-1 pl-5">
                @foreach (session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.members.import.store') }}" enctype="multipart/form-data" class="max-w-sm space-y-4">
        @csrf

        <div>
            <label for="file" class="block text-sm font-medium">Fichier CSV</label>
            <input id="file" type="file" name="file" accept=".csv,text/csv" required
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black">
            Importer
        </button>
    </form>
@endsection
