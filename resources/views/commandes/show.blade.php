@extends('layouts.app')

@section('title', 'Détails de la Commande')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Commande #CMD-{{ str_pad($commande->id, 6, '0', STR_PAD_LEFT) }}</h1>
                <p class="text-muted mb-0">Créée le {{ $commande->created_at->format('d/m/Y à H:i') }}</p>
            </div>
            <div>
                <a href="{{ route('commandes.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                @if($commande->etat == 'en_cours')
                    <a href="{{ route('commandes.edit', $commande) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Informations principales -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informations de la commande</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Fournisseur:</strong></td>
                        <td>{{ $commande->fournisseur->nom }} ({{ $commande->fournisseur->numero }})</td>
                    </tr>
                    <tr>
                        <td><strong>Date commande:</strong></td>
                        <td>{{ $commande->date_commande->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Livraison prévue:</strong></td>
                        <td>{{ $commande->date_livraison_prevue->format('d/m/Y') }}</td>
                    </tr>
                    @if($commande->date_livraison_reelle)
                    <tr>
                        <td><strong>Livraison réelle:</strong></td>
                        <td class="text-success">{{ $commande->date_livraison_reelle->format('d/m/Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>État:</strong></td>
                        <td>
                            <span class="badge badge-etat {{ $commande->etat == 'en_cours' ? 'badge-en_cours' : 
                                                           ($commande->etat == 'livre' ? 'badge-livre' : 
                                                           ($commande->etat == 'paye' ? 'badge-paye' : 'badge-annule')) }}">
                                {{ $commande->etat == 'en_cours' ? 'En cours' : 
                                  ($commande->etat == 'livre' ? 'Livré' : 
                                  ($commande->etat == 'paye' ? 'Payé' : 'Annulé')) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Paiement -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Paiement</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="text-primary">{{ number_format($commande->montant_total, 0, ',', ' ') }} FCFA</h4>
                    <p class="text-muted mb-1">Montant total</p>
                </div>
                
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar {{ $pourcentagePaye == 100 ? 'bg-success' : 
                                               ($pourcentagePaye > 0 ? 'bg-warning' : 'bg-danger') }}" 
                         role="progressbar" 
                         style="width: {{ $pourcentagePaye }}%"
                         aria-valuenow="{{ $pourcentagePaye }}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        {{ round($pourcentagePaye) }}%
                    </div>
                </div>
                
                <table class="table table-sm">
                    <tr>
                        <td>Montant payé:</td>
                        <td class="text-end text-success">
                            {{ number_format($montantVerse, 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                    <tr>
                        <td>Montant restant:</td>
                        <td class="text-end text-danger fw-bold">
                            {{ number_format($montantRestant, 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                </table>
                
                @if($montantRestant > 0 && $commande->etat === 'livre' && $commande->date_livraison_reelle && Auth::user()->isResponsablePaiement())
                <div class="d-grid">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paiementModal">
                        <i class="bi bi-cash-coin"></i> Enregistrer un paiement
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Produits commandés -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Produits commandés</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Quantité</th>
                                <th>Prix d'achat</th>
                                <th>Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commande->produits as $produit)
                            @php
                                $pivot = $produit->pivot;
                                $sousTotal = $pivot->quantite * $pivot->prix_achat;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $produit->designation }}</strong><br>
                                    <small class="text-muted">
                                        Code: {{ $produit->code }} | 
                                        Catégorie: {{ $produit->sousCategorie->categorie->nom }}
                                    </small>
                                </td>
                                <td>{{ $pivot->quantite }}</td>
                                <td>{{ number_format($pivot->prix_achat, 0, ',', ' ') }} FCFA</td>
                                <td class="fw-bold">{{ number_format($sousTotal, 0, ',', ' ') }} FCFA</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td class="fw-bold">{{ number_format($commande->montant_total, 0, ',', ' ') }} FCFA</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Historique des versements -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Historique des versements</h6>
                <span class="badge bg-info">{{ $commande->versements->count() }} versement(s)</span>
            </div>
            <div class="card-body">
                @if($commande->versements->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>N° Versement</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commande->versements as $versement)
                            <tr>
                                <td>{{ $versement->numero_versement }}</td>
                                <td>{{ $versement->date_versement->format('d/m/Y') }}</td>
                                <td class="text-success">
                                    {{ number_format($versement->montant, 0, ',', ' ') }} FCFA
                                </td>
                                <td>
                                    @if(Auth::user()->isResponsablePaiement())
                                    <form action="{{ route('versements.destroy', $versement) }}" 
                                          method="POST" 
                                          style="display: inline;"
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce versement?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-cash-coin" style="font-size: 3rem;"></i>
                    <p class="mt-2">Aucun versement enregistré pour cette commande</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal de paiement -->
<div class="modal fade" id="paiementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enregistrer un paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('versements.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="commande_id" value="{{ $commande->id }}">
                    
                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant (FCFA) *</label>
                        <input type="number" step="0.01" class="form-control" id="montant" name="montant" 
                               required max="{{ $montantRestant }}">
                        <small class="text-muted">Maximum: {{ number_format($montantRestant, 0, ',', ' ') }} FCFA</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_versement" class="form-label">Date du versement *</label>
                        <input type="date" class="form-control" id="date_versement" name="date_versement" 
                               value="{{ date('Y-m-d') }}" required>
                    </div>
                    
                    @if($commande->date_livraison_reelle && $montantRestant > 100000)
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="generer_echelonne" name="generer_echelonne">
                        <label class="form-check-label" for="generer_echelonne">
                            Générer 3 versements échelonnés (5 jours d'intervalle)
                        </label>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Limiter le montant saisi au montant restant
$('#montant').on('input', function() {
    const max = parseFloat($(this).attr('max'));
    const valeur = parseFloat($(this).val()) || 0;
    
    if (valeur > max) {
        $(this).val(max);
        alert('Le montant ne peut pas dépasser ' + max.toLocaleString('fr-FR') + ' FCFA');
    }
});
</script>
@endpush
@endsection
