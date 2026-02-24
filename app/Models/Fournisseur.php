<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fournisseur extends Model
{
    use HasFactory;

    protected $fillable = ['numero', 'nom', 'adresse', 'archive'];

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function detteTotale()
    {
        return $this->commandes()
            ->whereIn('etat', ['validee', 'recue'])
            ->sum('montant_total');
    }
}
