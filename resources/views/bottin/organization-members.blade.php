{{-- Loaded into the members dialog of the home page. --}}
<div>
    <span class="inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium uppercase tracking-wide text-gray-600">
        {{ $organization->level->label() }}
    </span>
    <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ $organization->name }}</h2>

    @foreach ($organization->responsables as $responsable)
        <p @class(['text-sm text-gray-500', 'mt-2' => $loop->first, 'mt-0.5' => ! $loop->first])>
            <span class="text-gray-400">Responsable :</span>
            {{ $responsable->name }}
            <span class="text-gray-300">·</span>
            <a href="mailto:{{ $responsable->email }}" class="hover:underline">{{ $responsable->email }}</a>
            @if ($responsable->cell_phone)
                <span class="text-gray-300">·</span>
                <x-member-phone :member="$responsable" />
            @endif
        </p>
    @endforeach

    @if ($memberRoles->isNotEmpty())
        {{-- A table so emails and phones each start on a common column, whatever their length. --}}
        <table class="mt-4 w-full border-t border-gray-100 text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach ($memberRoles as $memberRole)
                    <tr class="align-baseline odd:bg-gray-200/75">
                        <td class="py-2 pl-2 pr-4">
                            <span class="font-medium text-gray-900">{{ $memberRole->role }}</span>
                            <span class="text-gray-300">·</span>
                            <span class="text-gray-700">{{ $memberRole->member->name }}</span>
                        </td>
                        <td class="py-2 pr-4 text-gray-500">
                            <a href="mailto:{{ $memberRole->member->email }}" class="hover:underline">{{ $memberRole->member->email }}</a>
                        </td>
                        <td class="whitespace-nowrap py-2 pr-2 text-gray-500">
                            <x-member-phone :member="$memberRole->member" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="mt-4 border-t border-gray-100 pt-4 text-sm text-gray-500">Aucun membre inscrit.</p>
    @endif
</div>
