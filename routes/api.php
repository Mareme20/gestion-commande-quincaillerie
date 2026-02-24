<?php

use App\Http\Controllers\CommandeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\VersementController;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/statistiques', [DashboardController::class, 'statistiques']);
Route::get('/dashboard/dette-totale', [DashboardController::class, 'detteTotale']);
Route::get('/dashboard/versements-journee', [DashboardController::class, 'versementsJournee']);

Route::apiResource('fournisseurs', FournisseurController::class);
Route::get('/fournisseurs/{id}/dette', [FournisseurController::class, 'detteFournisseur']);

Route::apiResource('commandes', CommandeController::class);
Route::post('/commandes/{id}/annuler', [CommandeController::class, 'annuler']);
Route::get('/commandes/en-cours', [CommandeController::class, 'commandesEnCours']);
Route::get('/commandes/livraisons-journee', [CommandeController::class, 'commandesLivraisonJournee']);
Route::get('/commandes/{id}/montant-restant', [CommandeController::class, 'montantRestant']);
Route::post('/commandes/{id}/generer-echelonnes', [CommandeController::class, 'genererVersementsEchelonnes']);

Route::apiResource('versements', VersementController::class);
Route::get('/commandes/{commandeId}/versements', [VersementController::class, 'historiqueVersements']);

Route::get('/categories/{category}', function (Categorie $category) {
    $category->load(['sousCategories' => function ($query) {
        $query->where('sous_categories.archive', false);
    }]);

    return response()->json([
        'id' => $category->id,
        'nom' => $category->nom,
        'sous_categories' => $category->sousCategories,
    ]);
});

Route::get('/produits', function () {
    return Produit::with('sousCategorie.categorie')
        ->where('produits.archive', false)
        ->get();
});
