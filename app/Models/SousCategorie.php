<?php
// app/Models/SousCategorie.php

// app/Models/SousCategorie.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SousCategorie extends Model
{
    use HasFactory;

    protected $fillable = ['categorie_id', 'nom', 'description', 'archive'];

    // Relation avec la catégorie parente
    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    // Relation avec les produits avec spécification explicite
    public function produits()
    {
        return $this->hasMany(Produit::class)->where('produits.archive', false);
    }

    // Relation avec tous les produits (sans filtre archive)
    public function tousProduits()
    {
        return $this->hasMany(Produit::class);
    }

    // Scope pour les sous-catégories actives
    public function scopeActive($query)
    {
        return $query->where('sous_categories.archive', false);
    }

    // Accessor pour le statut
    public function getStatutAttribute()
    {
        return $this->archive ? 'Archivée' : 'Active';
    }

    // Méthode pour compter les produits actifs
    public function getProduitsActifsCountAttribute()
    {
        return $this->produits()->where('produits.archive', false)->count();
    }
}