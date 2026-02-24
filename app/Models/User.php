<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = ['role','name','email','password'];
    protected $hidden = ['password','remember_token'];
    public function isGestionnaire() { return $this->role === 'gestionnaire'; }
    public function isResponsableAchat() { return $this->role === 'responsable_achat'; }
    public function isResponsablePaiement() { return $this->role === 'responsable_paiement'; }
}
