<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    public const ETAT_BROUILLON = 'brouillon';
    public const ETAT_VALIDEE = 'validee';
    public const ETAT_RECUE = 'recue';
    public const ETAT_CLOTUREE = 'cloturee';
    public const ETAT_ANNULE = 'annule';

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

    public function etatLabel(): string
    {
        return match ($this->etat) {
            self::ETAT_BROUILLON => 'Brouillon',
            self::ETAT_VALIDEE => 'Validée',
            self::ETAT_RECUE => 'Reçue',
            self::ETAT_CLOTUREE => 'Clôturée',
            self::ETAT_ANNULE => 'Annulée',
            default => ucfirst((string) $this->etat),
        };
    }

    public function etatBadgeClass(): string
    {
        return match ($this->etat) {
            self::ETAT_BROUILLON => 'badge-warning',
            self::ETAT_VALIDEE => 'badge-primary',
            self::ETAT_RECUE => 'badge-info',
            self::ETAT_CLOTUREE => 'badge-success',
            self::ETAT_ANNULE => 'badge-annule',
            default => 'badge-secondary',
        };
    }
}
