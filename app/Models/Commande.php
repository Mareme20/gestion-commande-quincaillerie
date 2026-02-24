<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'fournisseur_id',
        'date_commande',
        'montant_total',
        'date_livraison_prevue',
        'date_livraison_reelle',
        'etat'
    ];

    protected $casts = [
        'date_commande' => 'date',
        'date_livraison_prevue' => 'date',
        'date_livraison_reelle' => 'date',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'commande_produit')
                    ->withPivot('quantite', 'prix_achat', 'fournisseur_id')
                    ->withTimestamps();
    }

    public function versements()
    {
        return $this->hasMany(Versement::class);
    }

    public function montantRestant()
    {
        $versementsTotal = $this->versements()->sum('montant');
        return $this->montant_total - $versementsTotal;
    }
}
