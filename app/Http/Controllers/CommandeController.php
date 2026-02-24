<?php
// app/Http/Controllers/CommandeController.php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Produit;
use App\Models\Fournisseur;
use App\Models\Versement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CommandeController extends Controller
{
    public function index()
    {
        $commandes = Commande::with(['fournisseur', 'produits'])
            ->latest()
            ->get();
            
        return response()->json($commandes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'date_commande' => 'required|date',
            'date_livraison_prevue' => 'required|date|after_or_equal:date_commande',
            'produits' => 'required|array|min:1',
            'produits.*.id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|numeric|min:1',
            'produits.*.prix_achat' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        
        try {
            $montantTotal = 0;
            $produitsData = [];

            foreach ($request->produits as $produit) {
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
                return response()->json([
                    'message' => 'Un ou plusieurs produits ne sont pas rattachés au fournisseur sélectionné',
                    'produits' => $produitsIncompatibles->pluck('designation')->values(),
                ], 400);
            }

            $commande = Commande::create([
                'fournisseur_id' => $request->fournisseur_id,
                'date_commande' => $request->date_commande,
                'montant_total' => $montantTotal,
                'date_livraison_prevue' => $request->date_livraison_prevue,
                'etat' => Commande::ETAT_BROUILLON
            ]);

            $commande->produits()->attach($produitsData);

            DB::commit();

            return response()->json($commande->load(['fournisseur', 'produits']), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la création de la commande: ' . $e->getMessage()], 500);
        }
    }
public function genererVersementsEchelonnes(Request $request, $id)
{
    $commande = Commande::find($id);
    
    if (!$commande) {
        return response()->json(['message' => 'Commande non trouvée'], 404);
    }
    
    if (!$commande->date_livraison_reelle) {
        return response()->json(['message' => 'La commande doit être livrée pour générer les versements échelonnés'], 400);
    }
    
    $montantRestant = $commande->montantRestant();
    
    if ($montantRestant <= 0) {
        return response()->json(['message' => 'La commande est déjà payée'], 400);
    }
    
    $nombreVersements = min(3, ceil($montantRestant / 100000)); // Max 3 versements
    $montantVersement = $montantRestant / $nombreVersements;
    
    $versements = [];
    $dateVersement = Carbon::parse($commande->date_livraison_reelle);
    
    DB::beginTransaction();
    
    try {
        for ($i = 1; $i <= $nombreVersements; $i++) {
            $dateVersement->addDays(5);
            
            $numeroVersement = 'VERS-' . $dateVersement->format('Ym') . '-' . 
                str_pad(Versement::whereYear('created_at', $dateVersement->year)
                    ->whereMonth('created_at', $dateVersement->month)
                    ->count() + $i, 4, '0', STR_PAD_LEFT);
            
            $versement = Versement::create([
                'commande_id' => $commande->id,
                'numero_versement' => $numeroVersement,
                'date_versement' => $dateVersement->format('Y-m-d'),
                'montant' => $montantVersement
            ]);
            
            $versements[] = $versement;
        }
        
        DB::commit();
        
        return response()->json([
            'message' => "$nombreVersements versements échelonnés créés",
            'versements' => $versements,
            'prochain_versement' => $versements[0] ?? null
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Erreur lors de la génération des versements: ' . $e->getMessage()], 500);
    }
}
    public function show($id)
    {
        $commande = Commande::with(['fournisseur', 'produits.sousCategorie.categorie', 'versements'])
            ->find($id);
        
        if (!$commande) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        $montantRestant = $commande->montantRestant();

        return response()->json([
            'commande' => $commande,
            'montant_restant' => $montantRestant,
            'pourcentage_paye' => $commande->montant_total > 0 ? 
                (($commande->montant_total - $montantRestant) / $commande->montant_total) * 100 : 0
        ]);
    }

    public function update(Request $request, $id)
    {
        $commande = Commande::find($id);
        
        if (!$commande) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        if (!in_array($commande->etat, [Commande::ETAT_BROUILLON, Commande::ETAT_VALIDEE], true)) {
            return response()->json(['message' => 'Seules les commandes en brouillon ou validées peuvent être modifiées'], 400);
        }

        $validator = Validator::make($request->all(), [
            'date_livraison_prevue' => 'sometimes|required|date',
            'date_livraison_reelle' => 'nullable|date',
            'etat' => 'sometimes|in:brouillon,validee,recue,cloturee,annule'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $commande->update($request->all());

        return response()->json($commande->load(['fournisseur', 'produits']));
    }

    public function annuler($id)
    {
        $commande = Commande::find($id);
        
        if (!$commande) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        if (!in_array($commande->etat, [Commande::ETAT_BROUILLON, Commande::ETAT_VALIDEE], true)) {
            return response()->json(['message' => 'Seules les commandes en brouillon ou validées peuvent être annulées'], 400);
        }

        $commande->update(['etat' => Commande::ETAT_ANNULE]);

        return response()->json(['message' => 'Commande annulée avec succès']);
    }

    public function commandesEnCours()
    {
        $commandes = Commande::where('etat', Commande::ETAT_VALIDEE)
            ->with(['fournisseur', 'produits'])
            ->orderBy('date_livraison_prevue')
            ->get();

        return response()->json($commandes);
    }

    public function commandesLivraisonJournee()
    {
        $today = Carbon::today()->toDateString();
        
        $commandes = Commande::whereDate('date_livraison_prevue', $today)
            ->where('etat', Commande::ETAT_VALIDEE)
            ->with(['fournisseur', 'produits'])
            ->get();

        return response()->json($commandes);
    }

    public function filtrer(Request $request, $critere)
    {
        $query = Commande::query();
        
        switch ($critere) {
            case 'date':
                $date = $request->input('date');
                if ($date) {
                    $query->whereDate('date_commande', $date);
                }
                break;
                
            case 'fournisseur':
                $fournisseurId = $request->input('fournisseur_id');
                if ($fournisseurId) {
                    $query->where('fournisseur_id', $fournisseurId);
                }
                break;
                
            case 'montant':
                $min = $request->input('min_montant');
                $max = $request->input('max_montant');
                if ($min) $query->where('montant_total', '>=', $min);
                if ($max) $query->where('montant_total', '<=', $max);
                break;
        }

        $commandes = $query->with(['fournisseur', 'produits'])->get();

        return response()->json($commandes);
    }

    public function parEtat($etat)
    {
        $etatsValides = ['brouillon', 'validee', 'recue', 'cloturee', 'annule'];
        
        if (!in_array($etat, $etatsValides)) {
            return response()->json(['message' => 'État invalide'], 400);
        }

        $commandes = Commande::where('etat', $etat)
            ->with(['fournisseur', 'produits'])
            ->latest()
            ->get();

        return response()->json($commandes);
    }

    public function parDate($date)
    {
        try {
            $dateCarbon = Carbon::parse($date);
            
            $commandes = Commande::whereDate('date_commande', $dateCarbon)
                ->with(['fournisseur', 'produits'])
                ->latest()
                ->get();

            return response()->json($commandes);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Date invalide'], 400);
        }
    }

    public function montantRestant($id)
    {
        $commande = Commande::with('versements')->find($id);
        
        if (!$commande) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        $montantRestant = $commande->montantRestant();

        return response()->json([
            'commande_id' => $commande->id,
            'montant_total' => $commande->montant_total,
            'montant_paye' => $commande->montant_total - $montantRestant,
            'montant_restant' => $montantRestant,
            'est_payee' => $montantRestant == 0
        ]);
    }
}
