<?php
// app/Http/Controllers/Web/CommandeController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\Produit;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommandeController extends Controller
{
    private const ETATS_WORKFLOW = [
        Commande::ETAT_BROUILLON,
        Commande::ETAT_VALIDEE,
        Commande::ETAT_RECUE,
        Commande::ETAT_CLOTUREE,
        Commande::ETAT_ANNULE,
    ];

    public function index(Request $request)
    {
        $query = Commande::with(['fournisseur', 'produits']);
        
        // Filtres
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }
        
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }
        
        if ($request->filled('date_debut')) {
            $query->whereDate('date_commande', '>=', $request->date_debut);
        }
        
        if ($request->filled('date_fin')) {
            $query->whereDate('date_commande', '<=', $request->date_fin);
        }
        
        $commandes = $query->latest()->get();
        $fournisseurs = Fournisseur::where('fournisseurs.archive', false)->get();
        
        // Statistiques par état
        $stats = [
            'brouillon' => Commande::where('etat', Commande::ETAT_BROUILLON)->count(),
            'validee' => Commande::where('etat', Commande::ETAT_VALIDEE)->count(),
            'recue' => Commande::where('etat', Commande::ETAT_RECUE)->count(),
            'cloturee' => Commande::where('etat', Commande::ETAT_CLOTUREE)->count(),
            'annule' => Commande::where('etat', 'annule')->count(),
        ];
        
        return view('commandes.index', compact('commandes', 'fournisseurs', 'stats'));
    }
    
    public function create()
    {
        $fournisseurs = Fournisseur::where('fournisseurs.archive', false)->get();
        $produits = Produit::where('produits.archive', false)
            ->whereNotNull('fournisseur_id')
            ->with(['sousCategorie.categorie', 'fournisseur'])
            ->get();
            
        return view('commandes.create', compact('fournisseurs', 'produits'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'date_commande' => 'required|date',
            'date_livraison_prevue' => 'required|date|after_or_equal:date_commande',
            'produits' => 'required|array|min:1',
            'produits.*.id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|numeric|min:0.01',
            'produits.*.prix_achat' => 'required|numeric|min:0',
            'montant_total' => 'required|numeric|min:0',
        ]);
        
        DB::beginTransaction();
        
        try {
            $montantTotal = 0;
            $produitsData = [];
            
            foreach ($request->produits as $index => $produit) {
                $montantTotal += $produit['quantite'] * $produit['prix_achat'];
                
                $produitsData[$produit['id']] = [
                    'quantite' => $produit['quantite'],
                    'prix_achat' => $produit['prix_achat'],
                    'fournisseur_id' => $request->fournisseur_id
                ];
            }

            $produitIds = collect($request->produits)
                ->pluck('id')
                ->unique()
                ->values();
            $produitsCommande = Produit::whereIn('id', $produitIds)->get(['id', 'fournisseur_id', 'designation']);

            $produitsIncompatibles = $produitsCommande->filter(function ($produit) use ($request) {
                return !$produit->fournisseur_id || (int) $produit->fournisseur_id !== (int) $request->fournisseur_id;
            });

            if ($produitsIncompatibles->isNotEmpty()) {
                $noms = $produitsIncompatibles->pluck('designation')->implode(', ');
                return back()->withErrors([
                    'produits' => "Ces produits ne sont pas rattachés à ce fournisseur: {$noms}."
                ])->withInput();
            }
            
            // Vérifier que le montant total correspond
            if (abs($montantTotal - $request->montant_total) > 0.01) {
                return back()->withErrors(['montant_total' => 'Le montant total ne correspond pas aux produits.'])
                    ->withInput();
            }
            
            $commande = Commande::create([
                'fournisseur_id' => $request->fournisseur_id,
                'date_commande' => $request->date_commande,
                'montant_total' => $montantTotal,
                'date_livraison_prevue' => $request->date_livraison_prevue,
                'etat' => Commande::ETAT_BROUILLON
            ]);
            
            $commande->produits()->attach($produitsData);
            
            AuditLogger::log('commande.create', $commande, [
                'fournisseur_id' => $commande->fournisseur_id,
                'montant_total' => $commande->montant_total,
                'etat' => $commande->etat,
            ]);

            DB::commit();
            
            return redirect()->route('commandes.show', $commande)
                ->with('success', 'Commande créée avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la création de la commande: ' . $e->getMessage()])
                ->withInput();
        }
    }
    
    public function show(Commande $commande)
    {
        $commande->load(['fournisseur', 'produits.sousCategorie.categorie', 'versements']);
        
        $montantVerse = $commande->versements->sum('montant');
        $montantRestant = $commande->montant_total - $montantVerse;
        $pourcentagePaye = $commande->montant_total > 0 ? 
            ($montantVerse / $commande->montant_total) * 100 : 0;
        
        return view('commandes.show', compact('commande', 'montantVerse', 'montantRestant', 'pourcentagePaye'));
    }
    
    public function edit(Commande $commande)
    {
        if (!in_array($commande->etat, [Commande::ETAT_BROUILLON, Commande::ETAT_VALIDEE], true)) {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Seules les commandes en brouillon ou validées peuvent être modifiées.');
        }
        
        $commande->load(['produits']);
        $fournisseurs = Fournisseur::where('fournisseurs.archive', false)->get();

        if (!view()->exists('commandes.edit')) {
            return redirect()->route('commandes.show', $commande)
                ->with('info', 'La modification se fait depuis la fiche commande.');
        }
        
        return view('commandes.edit', compact('commande', 'fournisseurs'));
    }
    
    public function update(Request $request, Commande $commande)
    {
        if (!in_array($commande->etat, [Commande::ETAT_BROUILLON, Commande::ETAT_VALIDEE], true)) {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Seules les commandes en brouillon ou validées peuvent être modifiées.');
        }
        
        $request->validate([
            'date_livraison_prevue' => 'sometimes|required|date',
            'date_livraison_reelle' => 'nullable|date',
            'etat' => 'sometimes|in:' . implode(',', self::ETATS_WORKFLOW)
        ]);
        
        $commande->update($request->only(['date_livraison_prevue', 'date_livraison_reelle', 'etat']));
        AuditLogger::log('commande.update', $commande, $request->only(['date_livraison_prevue', 'date_livraison_reelle', 'etat']));
        
        return redirect()->route('commandes.show', $commande)
            ->with('success', 'Commande mise à jour avec succès.');
    }
    
    public function annuler(Commande $commande)
    {
        if (!in_array($commande->etat, [Commande::ETAT_BROUILLON, Commande::ETAT_VALIDEE], true)) {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Seules les commandes en brouillon ou validées peuvent être annulées.');
        }
        
        $commande->update(['etat' => Commande::ETAT_ANNULE]);
        AuditLogger::log('commande.cancel', $commande, ['etat' => $commande->etat]);
        
        return redirect()->route('commandes.index')
            ->with('success', 'Commande annulée avec succès.');
    }
    
    public function destroy(Commande $commande)
    {
        if ($commande->etat !== Commande::ETAT_BROUILLON) {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Seules les commandes brouillon peuvent être supprimées.');
        }
        
        $commande->delete();
        AuditLogger::log('commande.delete', $commande);
        
        return redirect()->route('commandes.index')
            ->with('success', 'Commande supprimée avec succès.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Commande::with(['fournisseur', 'versements']);

        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('date_commande', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_commande', '<=', $request->date_fin);
        }

        $commandes = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="commandes.csv"',
        ];

        return response()->stream(function () use ($commandes) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Numero', 'Fournisseur', 'Date commande', 'Date livraison prevue', 'Date livraison reelle', 'Etat', 'Montant total', 'Montant verse', 'Montant restant'], ';');

            foreach ($commandes as $commande) {
                $montantVerse = $commande->versements->sum('montant');
                fputcsv($handle, [
                    'CMD-' . str_pad((string) $commande->id, 6, '0', STR_PAD_LEFT),
                    $commande->fournisseur?->nom,
                    optional($commande->date_commande)->format('Y-m-d'),
                    optional($commande->date_livraison_prevue)->format('Y-m-d'),
                    optional($commande->date_livraison_reelle)->format('Y-m-d'),
                    $commande->etatLabel(),
                    $commande->montant_total,
                    $montantVerse,
                    $commande->montant_total - $montantVerse,
                ], ';');
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function valider(Commande $commande)
    {
        Gate::authorize('valider-commande', $commande);

        if ($commande->etat !== Commande::ETAT_BROUILLON) {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Seules les commandes brouillon peuvent être validées.');
        }

        $commande->update(['etat' => Commande::ETAT_VALIDEE]);
        AuditLogger::log('commande.validate', $commande, ['etat' => $commande->etat]);

        return redirect()->route('commandes.show', $commande)
            ->with('success', 'Commande validée avec succès.');
    }

    public function receptionner(Request $request, Commande $commande)
    {
        Gate::authorize('receptionner-commande', $commande);

        if ($commande->etat !== Commande::ETAT_VALIDEE) {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Seules les commandes validées peuvent être réceptionnées.');
        }

        $request->validate([
            'date_livraison_reelle' => 'required|date|after_or_equal:date_commande',
        ]);

        $commande->update([
            'etat' => Commande::ETAT_RECUE,
            'date_livraison_reelle' => $request->date_livraison_reelle,
        ]);
        AuditLogger::log('commande.receive', $commande, [
            'etat' => $commande->etat,
            'date_livraison_reelle' => $commande->date_livraison_reelle?->toDateString(),
        ]);

        return redirect()->route('commandes.show', $commande)
            ->with('success', 'Commande réceptionnée avec succès.');
    }

    public function bonCommande(Commande $commande)
    {
        $commande->load(['fournisseur', 'produits.sousCategorie.categorie']);

        return view('commandes.documents.bon-commande', [
            'commande' => $commande,
            'dateEdition' => now(),
        ]);
    }

    public function bonReception(Commande $commande)
    {
        if (!$commande->date_livraison_reelle || !in_array($commande->etat, [Commande::ETAT_RECUE, Commande::ETAT_CLOTUREE], true)) {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Le bon de réception est disponible uniquement après réception.');
        }

        $commande->load(['fournisseur', 'produits.sousCategorie.categorie']);

        return view('commandes.documents.bon-reception', [
            'commande' => $commande,
            'dateEdition' => now(),
        ]);
    }
}
