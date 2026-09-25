<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationLevel;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $level = OrganizationLevel::tryFrom(Str::lower(Str::ascii($data['level'])));

        if (! $level) {
            throw new \RuntimeException('niveau invalide ("'.$data['level'].'").');
        }

        if ($data['name'] === '' || $data['responsable_name'] === '') {
            throw new \RuntimeException('nom de l\'organisation ou du responsable manquant.');
        }

        if (! filter_var($data['responsable_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('courriel du responsable invalide ("'.$data['responsable_email'].'").');
        }

        if ($level === OrganizationLevel::Provincial && $data['parent_responsable_email'] !== '') {
            throw new \RuntimeException('une organisation provinciale ne peut pas avoir de parent.');
        }

        $parent = null;

        if ($level !== OrganizationLevel::Provincial) {
            if ($data['parent_responsable_email'] === '') {
                throw new \RuntimeException('courriel du responsable parent manquant.');
            }

            // A responsable may be in charge of several organizations: only look at the level right above.
            $candidates = Organization::where('responsable_email', $data['parent_responsable_email'])
                ->get()
                ->filter(fn (Organization $organization) => $organization->level->childLevel() === $level);

            if ($candidates->isEmpty()) {
                throw new \RuntimeException('organisation parente introuvable au niveau attendu.');
            }

            if ($candidates->count() > 1) {
                throw new \RuntimeException('plusieurs organisations parentes possibles ('.$candidates->pluck('name')->join(', ').').');
            }

            $parent = $candidates->first();
        }

        $duplicate = Organization::where('level', $level)
            ->where('parent_id', $parent?->id)
            ->where('name', $data['name'])
            ->exists();

        if ($duplicate) {
            throw new \RuntimeException('cette organisation existe déjà ("'.$data['name'].'").');
        }

        // A responsable already on file keeps the coordinates they have there.
        $existingResponsable = Organization::where('responsable_email', $data['responsable_email'])->first();

        Organization::create([
            'level' => $level,
            'parent_id' => $parent?->id,
            'name' => $data['name'],
            'responsable_name' => $existingResponsable->responsable_name ?? $data['responsable_name'],
            'responsable_email' => $data['responsable_email'],
            'responsable_cell_phone' => $existingResponsable ? $existingResponsable->getAttributes()['responsable_cell_phone'] : $data['responsable_cell_phone'],
        ]);
    }
}
