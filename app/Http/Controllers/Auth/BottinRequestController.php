<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BottinRequest;
use App\Models\Member;
use App\Models\MemberRole;
use App\Notifications\BottinChangeConfirmationNotification;
use App\Notifications\BottinRemovalRequestNotification;
use App\Support\BottinRequester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * From the declaration page, a visitor asks to change the coordinates of some
 * of their roles, or to be removed from the bottin for some of them.
 */
class BottinRequestController extends Controller
{
    public function modify(Request $request): RedirectResponse
    {
        $requester = $this->requester();

        $validated = $request->validateWithBag('modification', [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in($requester->roles->pluck('key'))],
            'new_email' => ['required', 'string', 'email:rfc,strict', 'max:255'],
            'new_cell_phone' => ['required', 'string', 'max:255'],
            'new_extension' => ['nullable', 'string', 'max:20'],
        ], [
            'roles.required' => 'Choisissez au moins un rôle à modifier.',
            'new_email.required' => 'La nouvelle adresse courriel est obligatoire.',
            'new_email.email' => 'La nouvelle adresse courriel doit être une adresse valide.',
            'new_cell_phone.required' => 'Le nouveau numéro de téléphone est obligatoire.',
        ]);

        $bottinRequest = BottinRequest::create([
            'type' => BottinRequest::MODIFICATION,
            'name' => $requester->name,
            'email' => $requester->email,
            'roles' => $this->selectedRoles($requester, $validated['roles']),
            'new_email' => mb_strtolower($validated['new_email']),
            'new_cell_phone' => $validated['new_cell_phone'],
            'new_extension' => $validated['new_extension'] ?? null,
        ]);

        Notification::route('mail', $bottinRequest->new_email)
            ->notify(new BottinChangeConfirmationNotification($bottinRequest));

        return back()->with('status', "Un courriel a été envoyé à {$bottinRequest->new_email}. Suivez le lien qu'il contient pour confirmer la modification.");
    }

    public function remove(Request $request): RedirectResponse
    {
        $requester = $this->requester();

        $validated = $request->validateWithBag('retrait', [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in($requester->roles->pluck('key'))],
        ], [
            'roles.required' => 'Choisissez au moins un rôle à retirer.',
        ]);

        $roles = $this->selectedRoles($requester, $validated['roles']);

        BottinRequest::create([
            'type' => BottinRequest::REMOVAL,
            'name' => $requester->name,
            'email' => $requester->email,
            'roles' => $roles,
            'confirmed_at' => now(),
        ]);

        // Each responsable is told only about the roles under their care.
        $this->removalRecipients($roles)->each(function (array $rolesForRecipient, string $recipient) use ($requester) {
            Notification::route('mail', $recipient)
                ->notify(new BottinRemovalRequestNotification($requester->name, $requester->email, $rolesForRecipient));
        });

        return back()->with('status', 'Votre demande a été transmise : une notification a été envoyée au responsable concerné pour que votre nom et vos coordonnées soient retirés du bottin.');
    }

    /**
     * The link in the courriel sent to the new address: the change is applied
     * and its confirmation recorded.
     */
    public function confirm(BottinRequest $bottinRequest): View
    {
        abort_unless($bottinRequest->type === BottinRequest::MODIFICATION, 404);

        if (! $bottinRequest->confirmed_at) {
            DB::transaction(function () use ($bottinRequest) {
                $this->applyModification($bottinRequest);
                $bottinRequest->update(['confirmed_at' => now()]);
            });
        }

        return view('auth.bottin-request-confirmed', ['bottinRequest' => $bottinRequest]);
    }

    private function requester(): BottinRequester
    {
        $email = session('declaration_email');

        $requester = $email ? BottinRequester::forEmail($email) : null;

        abort_unless($requester, 403);

        return $requester;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, array{key: string, organization: string, role: string}>
     */
    private function selectedRoles(BottinRequester $requester, array $keys): array
    {
        return $requester->roles->whereIn('key', $keys)->values()->all();
    }

    /**
     * Who handles each role's removal: the responsables of its organization.
     *
     * @param  array<int, array{key: string, organization: string, role: string}>  $roles
     * @return Collection<string, array<int, array{organization: string, role: string}>>
     */
    private function removalRecipients(array $roles): Collection
    {
        $byRecipient = [];

        foreach ($roles as $role) {
            $recipients = MemberRole::find(substr($role['key'], strlen('member_role:')))
                ?->organization->responsables->pluck('email') ?? collect();

            foreach ($recipients->unique() as $recipient) {
                $byRecipient[mb_strtolower($recipient)][] = ['organization' => $role['organization'], 'role' => $role['role']];
            }
        }

        return collect($byRecipient);
    }

    private function applyModification(BottinRequest $bottinRequest): void
    {
        $memberRoleIds = collect($bottinRequest->roles)->pluck('key')->map(fn ($key) => (int) substr($key, strlen('member_role:')));
        $cellPhone = $bottinRequest->getRawOriginal('new_cell_phone');
        $extension = $bottinRequest->new_extension;

        $oldMember = Member::where('email', $bottinRequest->email)->first();

        if ($oldMember && $memberRoleIds->isNotEmpty()) {
            $roles = $oldMember->roles()->whereIn('id', $memberRoleIds)->get();
            $target = Member::where('email', $bottinRequest->new_email)->first();

            if (! $target && $roles->count() === $oldMember->roles()->count()) {
                // Every role moves: the person simply changes coordinates.
                $oldMember->forceFill(['email' => $bottinRequest->new_email])->fill(['cell_phone' => $cellPhone, 'extension' => $extension])->save();
            } else {
                $target ??= Member::create(['name' => $oldMember->name, 'email' => $bottinRequest->new_email]);
                $target->forceFill(['cell_phone' => $cellPhone, 'extension' => $extension])->save();
                MemberRole::whereIn('id', $roles->pluck('id'))->update(['member_id' => $target->id]);

                if ($oldMember->roles()->doesntExist()) {
                    $oldMember->personalFilters()->delete();
                    $oldMember->delete();
                }
            }
        }
    }
}
