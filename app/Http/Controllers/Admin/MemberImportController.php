<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberImportController extends Controller
{
    /**
     * The CSV header this importer expects, in order.
     */
    private const COLUMNS = ['organization_responsable_email', 'role', 'name', 'email', 'cell_phone'];

    public function create(): View
    {
        return view('admin.members.import', [
            'columns' => self::COLUMNS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        fgetcsv($handle);

        $created = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if (count($row) < count(self::COLUMNS)) {
                $errors[] = "Ligne {$line} : nombre de colonnes invalide.";

                continue;
            }

            $data = array_combine(self::COLUMNS, array_map('trim', array_slice($row, 0, count(self::COLUMNS))));

            try {
                $this->createFromRow($data);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Ligne {$line} ({$data['email']}) : {$e->getMessage()}";
            }
        }

        fclose($handle);

        return redirect()->route('admin.members.import.create')
            ->with('status', "{$created} membre(s) créé(s).")
            ->with('import_errors', $errors);
    }

    /**
     * @param  array<string, string>  $data
     */
    private function createFromRow(array $data): void
    {
        $organization = Organization::where('responsable_email', $data['organization_responsable_email'])->first();

        if (! $organization) {
            throw new \RuntimeException('organisation introuvable.');
        }

        if (Member::where('email', $data['email'])->exists()) {
            throw new \RuntimeException('cette adresse courriel est déjà utilisée.');
        }

        Member::create([
            'organization_id' => $organization->id,
            'role' => $data['role'],
            'name' => $data['name'],
            'email' => $data['email'],
            'cell_phone' => $data['cell_phone'] !== '' ? $data['cell_phone'] : null,
        ]);
    }
}
