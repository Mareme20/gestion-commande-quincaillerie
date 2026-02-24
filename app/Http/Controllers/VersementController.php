<?php
// app/Http/Controllers/VersementController.php

namespace App\Http\Controllers;

use App\Models\Versement;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VersementController extends Controller
{
    public function index()
    {
        $versements = Versement::with('commande.fournisseur')
            ->latest()
            ->get();
            
        return response()->json($versements);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commande_id' => 'required|exists:commandes,id',
            'date_versement' => 'required|date',
            'montant' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $commande = Commande::find($request->commande_id);
        
        if ($commande->etat === 'annule') {
            return response()->json(['message' => 'Impossible d\'ajouter un versement à une commande annulée'], 400);
        }

        if (!$commande->date_livraison_reelle || $commande->etat !== Commande::ETAT_RECUE) {
            return response()->json([
                'message' => 'Le paiement est autorisé uniquement après la livraison réelle'
            ], 400);
        }

        $nombreVersements = $commande->versements()->count();
        if ($nombreVersements >= 3) {
            return response()->json([
                'message' => 'Le nombre maximum de versements (3) est atteint pour cette commande'
            ], 400);
        }

        if ($nombreVersements > 0) {
            $montantPremierVersement = $commande->versements()->first()->montant;
            if (abs($request->montant - $montantPremierVersement) > 1) {
                return response()->json([
                    'message' => 'Les versements doivent être égaux',
                    'montant_attendu' => $montantPremierVersement
                ], 400);
            }
        }

        $dateLivraisonReelle = Carbon::parse($commande->date_livraison_reelle);
        $dateVersement = Carbon::parse($request->date_versement);
        $dateMinimale = $dateLivraisonReelle->copy()->addDays(5);
        if ($dateVersement->lt($dateMinimale)) {
            return response()->json([
                'message' => 'Le premier versement doit être au moins 5 jours après la livraison réelle'
            ], 400);
        }

        if ($nombreVersements > 0) {
            $dernierVersement = $commande->versements()->latest()->first();
            $dateMinimaleSuivant = Carbon::parse($dernierVersement->date_versement)->addDays(5);
            if ($dateVersement->lt($dateMinimaleSuivant)) {
                return response()->json([
                    'message' => 'Le versement suivant doit être au moins 5 jours après le précédent'
                ], 400);
            }
        }

        // Vérifier le montant restant
        $montantRestant = $commande->montantRestant();
        
        if ($request->montant > $montantRestant) {
            return response()->json([
                'message' => 'Le montant du versement dépasse le montant restant à payer',
                'montant_restant' => $montantRestant
            ], 400);
        }

        DB::beginTransaction();
        
        try {
            // Générer le numéro de versement
            $date = Carbon::parse($request->date_versement);
            $numeroVersement = 'VERS-' . $date->format('Ym') . '-' . 
                str_pad(Versement::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count() + 1, 4, '0', STR_PAD_LEFT);

            $versement = Versement::create([
                'commande_id' => $request->commande_id,
                'numero_versement' => $numeroVersement,
                'date_versement' => $request->date_versement,
                'montant' => $request->montant
            ]);

            // Vérifier si la commande est maintenant totalement payée
            $nouveauMontantRestant = $commande->montantRestant();
            
            if ($nouveauMontantRestant == 0) {
                $commande->update(['etat' => Commande::ETAT_CLOTUREE]);
            }

            DB::commit();

            return response()->json($versement->load('commande.fournisseur'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de l\'enregistrement du versement: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $versement = Versement::with('commande.fournisseur')->find($id);
        
        if (!$versement) {
            return response()->json(['message' => 'Versement non trouvé'], 404);
        }

        return response()->json($versement);
    }

    public function update(Request $request, $id)
    {
        $versement = Versement::find($id);
        
        if (!$versement) {
            return response()->json(['message' => 'Versement non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'date_versement' => 'sometimes|required|date',
            'montant' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $versement->update($request->all());

        return response()->json($versement->load('commande.fournisseur'));
    }

    public function destroy($id)
    {
        $versement = Versement::find($id);
        
        if (!$versement) {
            return response()->json(['message' => 'Versement non trouvé'], 404);
        }

        DB::beginTransaction();
        
        try {
            $commande = $versement->commande;
            $versement->delete();
            
            // Recalculer l'état de la commande
            $montantRestant = $commande->montantRestant();
            
            if ($montantRestant > 0 && $commande->etat === Commande::ETAT_CLOTUREE) {
                $commande->update(['etat' => Commande::ETAT_RECUE]);
            }

            DB::commit();

            return response()->json(['message' => 'Versement supprimé avec succès']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la suppression du versement: ' . $e->getMessage()], 500);
        }
    }

    public function historiqueVersements($commandeId)
    {
        $commande = Commande::with(['versements', 'fournisseur'])->find($commandeId);
        
        if (!$commande) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        $montantRestant = $commande->montantRestant();

        return response()->json([
            'commande' => $commande,
            'versements' => $commande->versements,
            'statistiques' => [
                'montant_total' => $commande->montant_total,
                'total_verse' => $commande->montant_total - $montantRestant,
                'montant_restant' => $montantRestant,
                'nombre_versements' => $commande->versements->count(),
                'date_premier_versement' => $commande->versements->min('date_versement'),
                'date_dernier_versement' => $commande->versements->max('date_versement')
            ]
        ]);
    }
}
