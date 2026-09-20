<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Bottin de communication')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <a href="{{ route('bottin') }}" class="font-semibold">Bottin de la fédération</a>

                <nav class="flex items-center gap-4 text-sm">
                    @if (auth('web')->check())
                        <a href="{{ route('dashboard.properties') }}" class="text-gray-700 hover:underline">Mon organisation</a>
                        @if (auth('web')->user()->canCreateChildren())
                            <a href="{{ route('dashboard.organizations') }}" class="text-gray-700 hover:underline">Organisations</a>
                        @endif
                        <a href="{{ route('bottin.index') }}" class="text-gray-700 hover:underline">Membres</a>
                        @if (auth('web')->user()->organizationsManagedBySameResponsable()->count() > 1)
                            <a href="{{ route('dashboard.switch') }}" class="text-gray-700 hover:underline">Changer d'organisation</a>
                        @endif
                        <a href="{{ route('profile') }}" title="Propriétés" aria-label="Propriétés" class="text-gray-700 hover:text-gray-900">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-700 hover:underline">Déconnexion</button>
                        </form>
                    @elseif (auth('member')->check())
                        <a href="{{ route('bottin.index') }}" class="text-gray-700 hover:underline">Membres</a>
                        <a href="{{ route('profile') }}" title="Propriétés" aria-label="Propriétés" class="text-gray-700 hover:text-gray-900">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('member.logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-700 hover:underline">Déconnexion</button>
                        </form>
                    @elseif (auth('admin')->check())
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:underline">Administration</a>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-700 hover:underline">Déconnexion</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:underline">Connexion</a>
                    @endif
                </nav>
            </div>
        </header>

        @isset($identity)
            <div class="bg-black">
                <div class="mx-auto flex max-w-5xl flex-wrap items-baseline gap-x-6 gap-y-1 px-6 py-3 text-sm">
                    <span><span class="text-gray-400">Visiteur :</span> <span class="text-white">{{ $identity['name'] }}, {{ $identity['role'] }} de {{ $identity['organization'] }}</span></span>
                    <span><span class="text-gray-400">Responsable :</span> <span class="text-white">{{ $identity['responsable'] }}</span></span>
                </div>
            </div>
        @endisset

        <main class="mx-auto max-w-5xl px-6 py-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>

        <script>
            document.querySelectorAll('input[name="cell_phone"], input[name="responsable_cell_phone"]').forEach((input) => {
                input.addEventListener('input', function () {
                    const digits = this.value.replace(/\D/g, '').slice(0, 10);
                    let formatted = digits;
                    if (digits.length > 6) {
                        formatted = `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
                    } else if (digits.length > 3) {
                        formatted = `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
                    } else if (digits.length > 0) {
                        formatted = `(${digits}`;
                    }
                    this.value = formatted;
                });
            });
        </script>
    </body>
</html>
