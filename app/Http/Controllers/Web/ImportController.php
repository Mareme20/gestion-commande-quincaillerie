<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use App\Models\Produit;
use App\Models\SousCategorie;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function index()
    {
        return view('imports.index');
    }

    public function fournisseurs(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        [$rows, $headers] = $this->readCsv($request->file('file')->getRealPath());
        $required = ['numero', 'nom', 'adresse'];
        $missing = array_diff($required, $headers);
        if (!empty($missing)) {
            return back()->with('error', 'Colonnes manquantes: ' . implode(', ', $missing));
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            if (empty($row['numero']) || empty($row['nom']) || empty($row['adresse'])) {
                $errors[] = "Ligne {$line}: numero, nom et adresse sont obligatoires.";
                continue;
            }

            $model = Fournisseur::where('numero', trim($row['numero']))->first();
            if ($model) {
                $model->update([
                    'nom' => trim($row['nom']),
                    'adresse' => trim($row['adresse']),
                    'archive' => false,
                ]);
                $updated++;
            } else {
                Fournisseur::create([
                    'numero' => trim($row['numero']),
                    'nom' => trim($row['nom']),
                    'adresse' => trim($row['adresse']),
                    'archive' => false,
                ]);
                $created++;
            }
        }

        AuditLogger::log('import.fournisseurs', null, [
            'created' => $created,
            'updated' => $updated,
            'errors_count' => count($errors),
        ]);

        $msg = "Import fournisseurs terminé. Créés: {$created}, mis à jour: {$updated}.";
        if (!empty($errors)) {
            $msg .= ' Erreurs: ' . count($errors);
            return back()->with('warning', $msg)->with('import_errors', $errors);
        }

        return back()->with('success', $msg);
    }

    public function produits(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        [$rows, $headers] = $this->readCsv($request->file('file')->getRealPath());
        $required = ['code', 'designation', 'quantite_stock', 'prix_unitaire', 'sous_categorie', 'fournisseur_numero'];
        $missing = array_diff($required, $headers);
        if (!empty($missing)) {
            return back()->with('error', 'Colonnes manquantes: ' . implode(', ', $missing));
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $code = trim((string) ($row['code'] ?? ''));
            $designation = trim((string) ($row['designation'] ?? ''));
            $quantite = (float) ($row['quantite_stock'] ?? 0);
            $prix = (float) ($row['prix_unitaire'] ?? 0);
            $sousNom = trim((string) ($row['sous_categorie'] ?? ''));
            $fournisseurNumero = trim((string) ($row['fournisseur_numero'] ?? ''));

            if ($code === '' || $designation === '' || $sousNom === '' || $fournisseurNumero === '') {
                $errors[] = "Ligne {$line}: colonnes obligatoires manquantes.";
                continue;
            }
            if ($quantite < 0 || $prix < 0) {
                $errors[] = "Ligne {$line}: quantite_stock et prix_unitaire doivent être >= 0.";
                continue;
            }

            $sousCategorie = SousCategorie::where('nom', $sousNom)->where('archive', false)->first();
            if (!$sousCategorie) {
                $errors[] = "Ligne {$line}: sous-catégorie introuvable ({$sousNom}).";
                continue;
            }

            $fournisseur = Fournisseur::where('numero', $fournisseurNumero)->where('archive', false)->first();
            if (!$fournisseur) {
                $errors[] = "Ligne {$line}: fournisseur introuvable ({$fournisseurNumero}).";
                continue;
            }

            $model = Produit::where('code', $code)->first();
            $payload = [
                'designation' => $designation,
                'quantite_stock' => $quantite,
                'prix_unitaire' => $prix,
                'sous_categorie_id' => $sousCategorie->id,
                'fournisseur_id' => $fournisseur->id,
                'archive' => false,
            ];

            if ($model) {
                $model->update($payload);
                $updated++;
            } else {
                Produit::create(array_merge(['code' => $code], $payload));
                $created++;
            }
        }

        AuditLogger::log('import.produits', null, [
            'created' => $created,
            'updated' => $updated,
            'errors_count' => count($errors),
        ]);

        $msg = "Import produits terminé. Créés: {$created}, mis à jour: {$updated}.";
        if (!empty($errors)) {
            $msg .= ' Erreurs: ' . count($errors);
            return back()->with('warning', $msg)->with('import_errors', $errors);
        }

        return back()->with('success', $msg);
    }

    private function readCsv(string $path): array
    {
        $content = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$content || count($content) < 2) {
            return [[], []];
        }

        $delimiter = substr_count($content[0], ';') >= substr_count($content[0], ',') ? ';' : ',';
        $headers = array_map(fn ($h) => trim(mb_strtolower($h)), str_getcsv($content[0], $delimiter));

        $rows = [];
        for ($i = 1; $i < count($content); $i++) {
            $values = str_getcsv($content[$i], $delimiter);
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($values[$index]) ? trim($values[$index]) : null;
            }
            $rows[] = $row;
        }

        return [$rows, $headers];
    }
}
