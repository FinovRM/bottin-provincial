@extends('layouts.app')

@section('title', 'Bottin des organisations')

@section('content')
    @include('partials.hero-banner', [
        'title' => 'Bottin des organisations',
        'description' => 'Recherchez les organisations sous votre responsabilité.',
    ])

    <p class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:underline">← Mon tableau de bord</a>
    </p>

    <form method="GET" action="{{ route('dashboard.organizations') }}" class="mb-6 max-w-sm">
        <label for="q" class="block text-sm font-medium text-gray-500">Recherche par nom</label>
        <div class="relative mt-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
            </svg>
            <input id="q" type="text" name="q" value="{{ $query }}" placeholder="Nom de l'organisation…"
                class="w-full rounded-md border border-gray-300 py-2 pl-9 pr-3 text-sm">
        </div>
    </form>

    @php
        $responsableEmails = $organizations->pluck('responsable_email')->unique()->values();
    @endphp

    <div class="mb-4 flex flex-wrap gap-3">
        @if ($responsableEmails->isNotEmpty())
            <button type="button" id="copy-emails" data-emails="{{ $responsableEmails->implode(', ') }}"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Copier les courriels dans le presse-papier
            </button>

            <a href="{{ route('dashboard.organizations.export', request()->query()) }}"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Exporter la sélection en csv
            </a>
        @endif
    </div>

    <div class="space-y-3">
        @forelse ($organizations as $organization)
            @php
                $badgeColor = match ($organization->level->value) {
                    'provincial' => 'bg-indigo-100 text-indigo-700',
                    'regional' => 'bg-blue-100 text-blue-700',
                    default => 'bg-teal-100 text-teal-700',
                };
            @endphp
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $badgeColor }} font-semibold">
                            {{ mb_substr($organization->name, 0, 1) }}
                        </div>
                        <div>
                            <span class="inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium uppercase tracking-wide text-gray-600">
                                {{ $organization->level->label() }}
                            </span>
                            <p class="font-semibold text-gray-900">{{ $organization->name }}</p>
                            @if ($organization->legal_name && $organization->legal_name !== $organization->name)
                                <p class="text-xs text-gray-400">{{ $organization->legal_name }}</p>
                            @endif
                        </div>
                    </div>
                    @if ($organization->website)
                        <a href="{{ $organization->website }}" target="_blank" rel="noopener" class="shrink-0 text-sm text-blue-600 hover:underline">
                            Site web ↗
                        </a>
                    @endif
                </div>

                <dl class="mt-3 grid gap-x-6 gap-y-2 border-t border-gray-100 pt-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-400">Adresse</dt>
                        <dd class="text-gray-700">{{ $organization->fullPostalAddress() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-400">N° d'entreprise</dt>
                        <dd class="text-gray-700">{{ $organization->business_number ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-gray-400">Responsable</dt>
                        <dd class="text-gray-700">
                            {{ $organization->responsable_name }}
                            <span class="text-gray-300">·</span>
                            <a href="mailto:{{ $organization->responsable_email }}" class="hover:underline">{{ $organization->responsable_email }}</a>
                        </dd>
                    </div>
                </dl>
            </div>
        @empty
            <div class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow-sm">
                Aucune organisation trouvée.
            </div>
        @endforelse
    </div>

    <script>
        document.getElementById('copy-emails')?.addEventListener('click', function () {
            navigator.clipboard.writeText(this.dataset.emails).then(() => {
                const original = this.textContent;
                this.textContent = 'Courriels copiés !';
                setTimeout(() => { this.textContent = original; }, 2000);
            });
        });
    </script>
@endsection
