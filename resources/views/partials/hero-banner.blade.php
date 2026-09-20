<div class="-mt-8 mb-8 ml-[calc(50%-50vw)] mr-[calc(50%-50vw)] w-screen overflow-hidden bg-gradient-to-b from-slate-800 to-gray-50">
    <div class="grid">
        @isset($backgroundImage)
            <img src="{{ $backgroundImage }}" alt="" aria-hidden="true"
                class="pointer-events-none col-start-1 row-start-1 h-full w-1/2 max-w-md justify-self-end object-contain object-right opacity-10 sm:w-1/3">
        @endisset

        <div class="col-start-1 row-start-1 mx-auto w-full max-w-5xl px-6 pb-10 pt-8 sm:px-10">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-300">Bottin de communication</p>
            <h1 class="mt-1 text-3xl font-semibold text-white">{{ $title }}</h1>
            @isset($meta)
                <p class="mt-2">
                    <span class="inline-block rounded-full bg-slate-900/60 px-3 py-1 text-xs font-medium text-white">
                        {{ $meta }}
                    </span>
                </p>
            @endisset
            <p class="mt-2 max-w-2xl text-sm text-gray-300">{{ $description }}</p>
        </div>
    </div>
</div>
