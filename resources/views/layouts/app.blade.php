<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Bottin de communication')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
        {{-- Menu + identity banner stay pinned; their height is exposed as --sticky-top for sticky sidebars. --}}
        <div id="sticky-top" class="sticky top-0 z-40">
            <header class="border-b border-gray-200 bg-white">
                <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-y-2 px-6 py-4">
                    <a href="{{ route('bottin') }}" class="font-semibold">Bottin de la fédération</a>

                    <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                        @if (auth('web')->check())
                            {{-- A responsable only works on the properties of the chosen organization. --}}
                            @include('partials.role-menu')
                            @unless (\App\Support\VisitorIdentities::hasNoChosenRole())
                                <a href="{{ route('dashboard.properties') }}" class="text-gray-700 hover:underline">Mon organisation</a>
                            @endunless
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-gray-700 hover:underline">Déconnexion</button>
                            </form>
                        @elseif (auth('member')->check())
                            @php($roleChosen = ! auth('member')->user()->hasNoChosenRole())
                            @include('partials.role-menu')
                            @include('partials.home-icon')
                            @if ($roleChosen)
                                <a href="{{ route('bottin.organizations') }}" class="text-gray-700 hover:underline">Organisations</a>
                            @endif
                            <a href="{{ route('bottin.members') }}" class="text-gray-700 hover:underline">Membres</a>
                            @if ($roleChosen)
                                @include('partials.properties-icon', ['href' => route('profile')])
                            @endif
                            <form method="POST" action="{{ route('member.logout') }}">
                                @csrf
                                <button type="submit" class="text-gray-700 hover:underline">Déconnexion</button>
                            </form>
                        @elseif (auth('admin')->check())
                            <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:underline">Administration</a>
                            @include('partials.properties-icon', ['href' => route('admin.properties')])
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

            {{-- Every page of a logged-in visitor shows who they are. --}}
            @php($identity ??= auth('member')->check() || auth('web')->check() ? \App\Support\ViewerScope::identity() : null)
            @isset($identity)
                <div class="bg-black">
                    <div class="mx-auto flex max-w-5xl flex-wrap items-baseline gap-x-6 gap-y-1 px-6 py-3 text-sm">
                        <span><span class="text-gray-400">Visiteur :</span> <span class="text-white">{{ $identity['name'] }}, {{ $identity['role'] }}@if ($identity['organization']) de {{ $identity['organization'] }}@endif</span></span>
                        <span><span class="text-gray-400">Responsable :</span> <span class="text-white">{{ $identity['responsable'] }}</span></span>
                    </div>
                </div>
            @endisset
        </div>

        <main class="mx-auto max-w-5xl px-6 py-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>

        <script>
            const stickyTop = document.getElementById('sticky-top');
            new ResizeObserver(() => {
                document.documentElement.style.setProperty('--sticky-top', `${stickyTop.offsetHeight}px`);
            }).observe(stickyTop);

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
