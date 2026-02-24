@extends('layouts.app')

@section('title', 'Détails du Fournisseur')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">{{ $fournisseur->nom }}</h1>
                <p class="text-muted mb-0">N°: {{ $fournisseur->numero }}</p>
            </div>
            <div>
                <a href="{{ route('fournisseurs.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <a href="{{ route('fournisseurs.edit', $fournisseur) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informations du fournisseur</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Numéro:</strong></td>
                        <td>{{ $fournisseur->numero }}</td>
                    </tr>
                    <tr>
                        <td><strong>Adresse:</strong></td>
                        <td>{{ $fournisseur->adresse }}</td>
                    </tr>
                    <tr>
                        <td><strong>Statut:</strong></td>
                        <td>
                            @if($fournisseur->archive)
                                <span class="badge bg-danger">Archivé</span>
                            @else
                                <span class="badge bg-success">Actif</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Créé le:</strong></td>
                        <td>{{ $fournisseur->created_at->format('d/m/Y à H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Dette -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Dette totale</h6>
            </div>
            <div class="card-body text-center">
                @if($detteTotale > 0)
                <h2 class="text-danger">{{ number_format($detteTotale, 0, ',', ' ') }} FCFA</h2>
                <p class="text-muted">Montant total dû</p>
                <a href="{{ route('fournisseurs.dette', $fournisseur) }}" class="btn btn-outline-danger">
                    <i class="bi bi-cash-coin"></i> Voir le détail
                </a>
                @else
                <h4 class="text-success">Aucune dette</h4>
                <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">En cours</h6>
                                <h3 class="mb-0">{{ $commandesEnCours }}</h3>
                            </div>
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Livrées</h6>
                                <h3 class="mb-0">{{ $commandesLivrees }}</h3>
                            </div>
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Payées</h6>
                                <h3 class="mb-0">{{ $commandesPayees }}</h3>
                            </div>
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Commandes récentes -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Commandes récentes</h6>
                <span class="badge bg-info">{{ $fournisseur->commandes->count() }} commande(s)</span>
            </div>
            <div class="card-body">
                @if($fournisseur->commandes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>État</th>
                                <th>Paiement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fournisseur->commandes as $commande)
                            @php
                                $montantVerse = $commande->versements->sum('montant');
                                $montantRestant = $commande->montant_total - $montantVerse;
                                $pourcentagePaye = $commande->montant_total > 0 ? 
                                    ($montantVerse / $commande->montant_total) * 100 : 0;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('commandes.show', $commande) }}">
                                        CMD-{{ str_pad($commande->id, 6, '0', STR_PAD_LEFT) }}
                                    </a>
                                </td>
                                <td>{{ $commande->date_commande->format('d/m/Y') }}</td>
                                <td>{{ number_format($commande->montant_total, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    <span class="badge badge-etat {{ $commande->etat == 'en_cours' ? 'badge-en_cours' : 
                                                                   ($commande->etat == 'livre' ? 'badge-livre' : 
                                                                   ($commande->etat == 'paye' ? 'badge-paye' : 'badge-annule')) }}">
                                        {{ $commande->etat == 'en_cours' ? 'En cours' : 
                                          ($commande->etat == 'livre' ? 'Livré' : 
                                          ($commande->etat == 'paye' ? 'Payé' : 'Annulé')) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ round($pourcentagePaye) }}%</small>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar {{ $pourcentagePaye == 100 ? 'bg-success' : 
                                                                   ($pourcentagePaye > 0 ? 'bg-warning' : 'bg-danger') }}" 
                                             style="width: {{ $pourcentagePaye }}%">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                    <p class="mt-2">Aucune commande pour ce fournisseur</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection