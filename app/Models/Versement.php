<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Versement extends Model
{
    use HasFactory;

    protected $fillable = [
        'commande_id',
        'numero_versement',
        'date_versement',
        'montant'
    ];

    protected $casts = [
        'date_versement' => 'date',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }
}
