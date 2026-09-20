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

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-2">Organisation</th>
                    <th class="px-4 py-2">Niveau</th>
                    <th class="px-4 py-2">Nom légal</th>
                    <th class="px-4 py-2 min-w-[18rem]">Adresse</th>
                    <th class="px-4 py-2">N° d'entreprise</th>
                    <th class="px-4 py-2">Site web</th>
                    <th class="px-4 py-2">Responsable</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($organizations as $organization)
                    <tr class="border-b border-gray-100 last:border-0">
                        <td class="px-4 py-2 font-medium">{{ $organization->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $organization->level->label() }}</td>
                        <td class="px-4 py-2">{{ $organization->legal_name ?: '—' }}</td>
                        <td class="px-4 py-2 min-w-[18rem]">{{ $organization->fullPostalAddress() ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $organization->business_number ?: '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($organization->website)
                                <a href="{{ $organization->website }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ $organization->website }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            {{ $organization->responsable_name }}
                            <br><span class="text-gray-500">{{ $organization->responsable_email }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">Aucune organisation trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
