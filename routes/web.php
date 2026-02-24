<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\CategorieController;
use App\Http\Controllers\Web\SousCategorieController;
use App\Http\Controllers\Web\ProduitController;
use App\Http\Controllers\Web\FournisseurController;
use App\Http\Controllers\Web\CommandeController;
use App\Http\Controllers\Web\VersementController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\SearchController;
use App\Http\Controllers\Web\ImportController;

// Authentification
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Middleware d'authentification
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, '__invoke'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, '__invoke'])->name('dashboard');
    Route::get('/recherche', [SearchController::class, 'index'])->name('search.index');
    
    // Routes pour Gestionnaire
    Route::middleware(['can:gestionnaire'])->group(function () {
        // Catégories
        Route::resource('categories', CategorieController::class);
        Route::post('/categories/{category}/archive', [CategorieController::class, 'archive'])
            ->name('categories.archive');
        
        // Sous-catégories
        Route::resource('sous-categories', SousCategorieController::class)
            ->parameters(['sous-categories' => 'sousCategorie']);
        Route::post('/sous-categories/{sousCategorie}/archive', [SousCategorieController::class, 'archive'])
            ->name('sous-categories.archive');
        
        // Produits
        Route::resource('produits', ProduitController::class);
        Route::post('/produits/{produit}/archive', [ProduitController::class, 'archive'])
            ->name('produits.archive');
        Route::post('/produits/{produit}/upload-image', [ProduitController::class, 'uploadImage'])
            ->name('produits.upload-image');
        
        // Fournisseurs
        Route::resource('fournisseurs', FournisseurController::class);
        Route::post('/fournisseurs/{fournisseur}/archive', [FournisseurController::class, 'archive'])
            ->name('fournisseurs.archive');
        Route::get('/fournisseurs/{fournisseur}/dette', [FournisseurController::class, 'dette'])
            ->name('fournisseurs.dette');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit.index');
        Route::get('/imports', [ImportController::class, 'index'])
            ->name('imports.index');
        Route::post('/imports/fournisseurs', [ImportController::class, 'fournisseurs'])
            ->name('imports.fournisseurs');
        Route::post('/imports/produits', [ImportController::class, 'produits'])
            ->name('imports.produits');
    });
    
    // Routes pour Responsable Achat
    Route::middleware(['can:responsable_achat,gestionnaire'])->group(function () {
        // Commandes
        Route::resource('commandes', CommandeController::class);
        Route::get('/commandes-export/csv', [CommandeController::class, 'exportCsv'])
            ->name('commandes.export.csv');
        Route::get('/commandes/{commande}/bon-commande', [CommandeController::class, 'bonCommande'])
            ->name('commandes.bon-commande');
        Route::get('/commandes/{commande}/bon-reception', [CommandeController::class, 'bonReception'])
            ->name('commandes.bon-reception');
        Route::post('/commandes/{commande}/valider', [CommandeController::class, 'valider'])
            ->middleware('can:valider-commande,commande')
            ->name('commandes.valider');
        Route::post('/commandes/{commande}/receptionner', [CommandeController::class, 'receptionner'])
            ->middleware('can:receptionner-commande,commande')
            ->name('commandes.receptionner');
        Route::post('/commandes/{commande}/annuler', [CommandeController::class, 'annuler'])
            ->name('commandes.annuler');
        Route::get('/commandes/{commande}/generer-echelonnes', [VersementController::class, 'genererEchelonnes'])
            ->name('commandes.generer-echelonnes');
    });

    // Routes pour Responsable Paiement
    Route::middleware(['can:responsable_paiement,gestionnaire'])->group(function () {
        // Versements
        Route::resource('versements', VersementController::class);
        Route::get('/versements-export/csv', [VersementController::class, 'exportCsv'])
            ->name('versements.export.csv');
    });
});

// Redirection par défaut
Route::redirect('/home', '/dashboard');
