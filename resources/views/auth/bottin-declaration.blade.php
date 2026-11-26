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

            @if ($requester)
                <div class="mt-6 rounded-md border border-gray-200 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Demandeur</p>
                    <dl class="mt-2 space-y-1 text-sm">
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-gray-500">Nom</dt><dd class="font-medium text-gray-900">{{ $requester->name }}</dd></div>
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-gray-500">Courriel</dt><dd class="break-all text-gray-900">{{ $requester->email }}</dd></div>
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-gray-500">Téléphone</dt>
                            <dd class="text-gray-900">
                                @forelse ($requester->phones as $phone)
                                    <span class="block">{{ $phone }}</span>
                                @empty
                                    <span class="text-gray-400">Aucun</span>
                                @endforelse
                            </dd>
                        </div>
                    </dl>

                    @if ($requester->roles->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" onclick="document.getElementById('modification-dialog').showModal()"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Modifier
                            </button>
                            <button type="button" onclick="document.getElementById('retrait-dialog').showModal()"
                                class="rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50">
                                Retirez-moi du bottin
                            </button>
                        </div>
                    @endif
                </div>
            @endif

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

    @if ($requester && $requester->roles->isNotEmpty())
        @foreach (['modification' => ['Modifier mes coordonnées', 'bottin-requests.modify', 'Choisissez le ou les rôles à modifier.'], 'retrait' => ['Me retirer du bottin', 'bottin-requests.remove', 'Choisissez le ou les rôles pour lesquels vous voulez être retiré du bottin.']] as $kind => [$title, $route, $help])
            @php($bag = $errors->getBag($kind))
            <dialog id="{{ $kind }}-dialog" @if ($bag->any()) data-open @endif
                class="w-full max-w-md rounded-lg border border-gray-200 p-6 backdrop:bg-black/30">
                <form method="POST" action="{{ route($route) }}">
                    @csrf
                    <h3 class="text-lg font-semibold">{{ $title }}</h3>

                    <div class="mt-3 rounded-md bg-gray-50 p-3 text-sm">
                        <p class="font-medium text-gray-900">{{ $requester->name }}</p>
                        <p class="break-all text-gray-700">{{ $requester->email }}</p>
                        @foreach ($requester->phones as $phone)
                            <p class="text-gray-700">{{ $phone }}</p>
                        @endforeach
                    </div>

                    <fieldset class="mt-4">
                        <legend class="text-sm font-medium">{{ $help }}</legend>
                        <div class="mt-2 space-y-2">
                            @foreach ($requester->roles as $role)
                                <label class="flex items-start gap-2 text-sm">
                                    <input type="checkbox" name="roles[]" value="{{ $role['key'] }}" class="mt-0.5 h-4 w-4"
                                        @checked($bag->any() && in_array($role['key'], old('roles', [])))>
                                    <span><span class="font-medium">{{ $role['role'] }}</span> — {{ $role['organization'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @if ($bag->has('roles') || $bag->has('roles.*'))<p class="mt-1 text-sm text-red-600">{{ $bag->first('roles') ?: $bag->first('roles.*') }}</p>@endif
                    </fieldset>

                    @if ($kind === 'modification')
                        @php($phone = $requester->primaryPhone())
                        <div class="mt-4">
                            <label for="new_email" class="block text-sm font-medium">Nouvelle adresse courriel</label>
                            <input id="new_email" type="email" name="new_email" required value="{{ $bag->any() ? old('new_email') : '' }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            @if ($bag->has('new_email'))<p class="mt-1 text-sm text-red-600">{{ $bag->first('new_email') }}</p>@endif
                        </div>
                        <div class="mt-3 flex gap-3">
                            <div class="grow">
                                <label for="new_cell_phone" class="block text-sm font-medium">Nouveau numéro de téléphone</label>
                                <input id="new_cell_phone" type="tel" name="new_cell_phone" required
                                    value="{{ $bag->any() ? old('new_cell_phone') : $phone['cell_phone'] }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                @if ($bag->has('new_cell_phone'))<p class="mt-1 text-sm text-red-600">{{ $bag->first('new_cell_phone') }}</p>@endif
                            </div>
                            <div class="w-24">
                                <label for="new_extension" class="block text-sm font-medium">Poste</label>
                                <input id="new_extension" type="text" name="new_extension"
                                    value="{{ $bag->any() ? old('new_extension') : $phone['extension'] }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Un courriel sera envoyé à la nouvelle adresse pour confirmer la demande.</p>
                    @else
                        <p class="mt-3 text-xs text-gray-500">Le responsable de chaque organisation concernée sera avisé de retirer votre nom et vos coordonnées.</p>
                    @endif

                    <div class="mt-6 flex gap-3">
                        <button type="button" onclick="this.closest('dialog').close()"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Annuler
                        </button>
                        <button type="submit"
                            class="rounded-md px-4 py-2 text-sm font-medium text-white {{ $kind === 'retrait' ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-900 hover:bg-black' }}">
                            Confirmer
                        </button>
                    </div>
                </form>
            </dialog>
        @endforeach

        <script>
            document.querySelectorAll('dialog[data-open]').forEach((dialog) => dialog.showModal());
        </script>
    @endif
@endsection
