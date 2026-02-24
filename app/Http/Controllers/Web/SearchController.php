<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\Produit;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $resultats = [
            'commandes' => collect(),
            'produits' => collect(),
            'fournisseurs' => collect(),
        ];

        if ($q !== '') {
            $resultats['commandes'] = Commande::with('fournisseur')
                ->where('id', 'like', "%{$q}%")
                ->orWhere('etat', 'like', "%{$q}%")
                ->latest()
                ->limit(10)
                ->get();

            $resultats['produits'] = Produit::with('fournisseur')
                ->where('archive', false)
                ->where(function ($query) use ($q) {
                    $query->where('code', 'like', "%{$q}%")
                        ->orWhere('designation', 'like', "%{$q}%");
                })
                ->latest()
                ->limit(10)
                ->get();

            $resultats['fournisseurs'] = Fournisseur::where('archive', false)
                ->where(function ($query) use ($q) {
                    $query->where('nom', 'like', "%{$q}%")
                        ->orWhere('numero', 'like', "%{$q}%")
                        ->orWhere('adresse', 'like', "%{$q}%");
                })
                ->latest()
                ->limit(10)
                ->get();
        }

        return view('search.index', [
            'q' => $q,
            'resultats' => $resultats,
        ]);
    }
}
