<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Gate pour le rôle gestionnaire
        Gate::define('gestionnaire', function (User $user) {
            return $user->isGestionnaire();
        });
        
        // Gate pour le rôle responsable achat
        Gate::define('responsable_achat', function (User $user) {
            return $user->isResponsableAchat();
        });
        
        // Gate pour le rôle responsable paiement
        Gate::define('responsable_paiement', function (User $user) {
            return $user->isResponsablePaiement();
        });
    }
}