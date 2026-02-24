@extends('layouts.app')

@section('title', 'Détails du Produit')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">{{ $produit->designation }}</h1>
                <p class="text-muted mb-0">Code: {{ $produit->code }}</p>
            </div>
            <div>
                <a href="{{ route('produits.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <a href="{{ route('produits.edit', $produit) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($produit->image)
                    <img src="{{ Storage::url($produit->image) }}" 
                         alt="{{ $produit->designation }}" 
                         class="img-fluid rounded mb-3"
                         style="max-height: 300px; object-fit: cover;">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center rounded mb-3" 
                         style="height: 300px;">
                        <i class="bi bi-image text-muted" style="font-size: 5rem;"></i>
                    </div>
                @endif
                
                <h4 class="text-primary">{{ number_format($produit->prix_unitaire, 0, ',', ' ') }} FCFA</h4>
                <p class="text-muted">Prix unitaire</p>
                
                <div class="d-grid">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
                        <i class="bi bi-upload"></i> Changer l'image
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Informations générales</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Code:</strong></td>
                        <td>{{ $produit->code }}</td>
                    </tr>
                    <tr>
                        <td><strong>Catégorie:</strong></td>
                        <td>
                            {{ $produit->sousCategorie->categorie->nom }}<br>
                            <small class="text-muted">{{ $produit->sousCategorie->nom }}</small>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Fournisseur:</strong></td>
                        <td>{{ $produit->fournisseur?->nom ?? 'Non défini' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Stock:</strong></td>
                        <td>
                            <span class="fw-bold">{{ $produit->quantite_stock }}</span>
                            @if($produit->quantite_stock < 10)
                                <span class="badge bg-danger ms-2">Faible</span>
                            @elseif($produit->quantite_stock < 50)
                                <span class="badge bg-warning ms-2">Moyen</span>
                            @else
                                <span class="badge bg-success ms-2">Bon</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Statut:</strong></td>
                        <td>
                            @if($produit->archive)
                                <span class="badge bg-danger">Archivé</span>
                            @else
                                <span class="badge bg-success">Actif</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Créé le:</strong></td>
                        <td>{{ $produit->created_at->format('d/m/Y à H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total vendu</h6>
                                <h3 class="mb-0">{{ $totalVendu }}</h3>
                            </div>
                            <i class="bi bi-cart-check" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Chiffre d'affaires</h6>
                                <h3 class="mb-0">{{ number_format($chiffreAffaires, 0, ',', ' ') }} FCFA</h3>
                            </div>
                            <i class="bi bi-currency-exchange" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historique des commandes -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Commandes incluant ce produit</h6>
                <span class="badge bg-info">{{ $produit->commandes->count() }} commande(s)</span>
            </div>
            <div class="card-body">
                @if($produit->commandes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Fournisseur</th>
                                <th>Date</th>
                                <th>Quantité</th>
                                <th>Prix d'achat</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($produit->commandes as $commande)
                            @php
                                $pivot = $commande->pivot;
                                $total = $pivot->quantite * $pivot->prix_achat;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('commandes.show', $commande) }}">
                                        CMD-{{ str_pad($commande->id, 6, '0', STR_PAD_LEFT) }}
                                    </a>
                                </td>
                                <td>{{ $commande->fournisseur->nom }}</td>
                                <td>{{ $commande->date_commande->format('d/m/Y') }}</td>
                                <td>{{ $pivot->quantite }}</td>
                                <td>{{ number_format($pivot->prix_achat, 0, ',', ' ') }} FCFA</td>
                                <td class="fw-bold">{{ number_format($total, 0, ',', ' ') }} FCFA</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                    <p class="mt-2">Ce produit n'a pas encore été commandé</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal pour upload d'image -->
<div class="modal fade" id="uploadImageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changer l'image du produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('produits.upload-image', $produit) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="image" class="form-label">Nouvelle image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                        <small class="text-muted">Taille max: 2MB, Formats: JPG, PNG, GIF</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Uploader</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
