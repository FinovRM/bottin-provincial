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
            <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4">
                <a href="{{ route('bottin') }}" class="font-semibold">Bottin de communication</a>

                <nav class="flex items-center gap-4 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:underline">Mon tableau de bord</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-700 hover:underline">Déconnexion</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:underline">Connexion</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-6 py-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </body>
</html>
