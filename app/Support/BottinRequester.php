<?php

namespace App\Support;

use App\Models\Member;
use App\Models\Responsable;
use Illuminate\Support\Collection;

/**
 * Who is on the declaration page: their name, courriel, every phone number
 * filed under that courriel, and every role (identity) they hold.
 */
class BottinRequester
{
    /**
     * @param  Collection<int, string>  $phones
     * @param  Collection<int, array{key: string, organization: string, role: string}>  $roles
     */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly Collection $phones,
        public readonly Collection $roles,
    ) {}

    public static function forEmail(string $email): ?self
    {
        $member = Member::where('email', $email)->first();
        $responsables = Responsable::where('email', $email)->get();

        $name = $member?->name ?? $responsables->first()?->name;

        if ($name === null) {
            return null;
        }

        $phones = collect([$member])->filter()->merge($responsables)
            ->filter(fn ($person) => $person->cell_phone)
            ->map(fn ($person) => $person->cell_phone.($person->extension ? " poste {$person->extension}" : ''))
            ->unique()
            ->values();

        // Member roles only: being responsable de bottin isn't requested from here.
        $roles = VisitorIdentities::forEmail($email)
            ->where('guard', 'member')
            ->map(fn (array $identity) => [
                'key' => $identity['key'],
                'organization' => $identity['organization'],
                'role' => $identity['role'],
            ])
            ->values();

        return new self($name, $email, $phones, $roles);
    }

    /**
     * The first phone on file, split for prefilling the modification form.
     *
     * @return array{cell_phone: ?string, extension: ?string}
     */
    public function primaryPhone(): array
    {
        $person = Member::where('email', $this->email)->first()
            ?? Responsable::where('email', $this->email)->first();

        return ['cell_phone' => $person?->cell_phone, 'extension' => $person?->extension];
    }
}
