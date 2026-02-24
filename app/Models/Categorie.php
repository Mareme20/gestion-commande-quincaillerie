<?php
// app/Models/Categorie.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'description', 'archive'];

    // Relation avec les sous-catégories
    public function sousCategories()
    {
        return $this->hasMany(SousCategorie::class);
    }

    // Relation avec les produits (via sous-catégories) avec spécification explicite
    public function produits()
    {
        return $this->hasManyThrough(
            Produit::class, 
            SousCategorie::class,
            'categorie_id', // Clé étrangère sur sous_categories
            'sous_categorie_id', // Clé étrangère sur produits
            'id', // Clé locale sur categories
            'id' // Clé locale sur sous_categories
        )->where('produits.archive', false); // Spécifier la table
    }

    // Méthode pour obtenir les produits actifs
    public function produitsActifs()
    {
        return $this->produits()->where('produits.archive', false);
    }

    // Méthode pour compter les produits actifs
    public function getProduitsActifsCountAttribute()
    {
        return $this->produitsActifs()->count();
    }

    // Scope pour les catégories actives
    public function scopeActive($query)
    {
        return $query->where('categories.archive', false); // Spécifier la table
    }

    // Méthode pour archiver/désarchiver
    public function toggleArchive()
    {
        $this->update(['archive' => !$this->archive]);
        return $this;
    }
}