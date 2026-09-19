<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationLevel;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationImportController extends Controller
{
    /**
     * The CSV header this importer expects, in order.
     */
    private const COLUMNS = [
        'level', 'parent_responsable_email', 'name',
        'responsable_name', 'responsable_email', 'responsable_cell_phone',
    ];

    public function create(): View
    {
        return view('admin.organizations.import', [
            'columns' => self::COLUMNS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

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
                $errors[] = "Ligne {$line} ({$data['responsable_email']}) : {$e->getMessage()}";
            }
        }

        fclose($handle);

        return redirect()->route('admin.organizations.import.create')
            ->with('status', "{$created} organisation(s) créée(s).")
            ->with('import_errors', $errors);
    }

    /**
     * @param  array<string, string>  $data
     */
    private function createFromRow(array $data): void
    {
        $level = OrganizationLevel::tryFrom($data['level']);

        if (! $level) {
            throw new \RuntimeException('niveau invalide ("'.$data['level'].'").');
        }

        $parent = null;

        if ($data['parent_responsable_email'] !== '') {
            $parent = Organization::where('responsable_email', $data['parent_responsable_email'])->first();

            if (! $parent) {
                throw new \RuntimeException('organisation parente introuvable.');
            }
        }

        if ($level === OrganizationLevel::Provincial && $parent !== null) {
            throw new \RuntimeException('une organisation provinciale ne peut pas avoir de parent.');
        }

        if ($level !== OrganizationLevel::Provincial && ($parent === null || $parent->level->childLevel() !== $level)) {
            throw new \RuntimeException('parent manquant ou incompatible avec le niveau.');
        }

        if (Organization::where('responsable_email', $data['responsable_email'])->exists()) {
            throw new \RuntimeException('cette adresse courriel est déjà utilisée.');
        }

        Organization::create([
            'level' => $level,
            'parent_id' => $parent?->id,
            'name' => $data['name'],
            'responsable_name' => $data['responsable_name'],
            'responsable_email' => $data['responsable_email'],
            'responsable_cell_phone' => $data['responsable_cell_phone'],
        ]);
    }
}
