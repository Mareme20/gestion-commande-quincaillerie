@extends('layouts.app')

@section('title', 'Gestion des Produits')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Gestion des Produits</h1>
            <div>
                <a href="{{ route('produits.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nouveau Produit
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('produits.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Recherche</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Code ou désignation">
                    </div>
                    <div class="col-md-3">
                        <label for="categorie_id" class="form-label">Catégorie</label>
                        <select class="form-select" id="categorie_id" name="categorie_id">
                            <option value="">Toutes les catégories</option>
                            @foreach($categories as $categorie)
                                <option value="{{ $categorie->id }}" 
                                    {{ request('categorie_id') == $categorie->id ? 'selected' : '' }}>
                                    {{ $categorie->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="stock_min" class="form-label">Stock minimum</label>
                        <input type="number" class="form-control" id="stock_min" name="stock_min" 
                               value="{{ request('stock_min') }}" placeholder="0">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Filtrer
                            </button>
                            <a href="{{ route('produits.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Image</th>
                                <th>Désignation</th>
                                <th>Fournisseur</th>
                                <th>Catégorie</th>
                                <th>Stock</th>
                                <th>Prix Unitaire</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($produits as $produit)
                            <tr>
                                <td>
                                    <strong>{{ $produit->code }}</strong>
                                </td>
                                <td>
                                    @if($produit->image)
                                        <img src="{{ Storage::url($produit->image) }}" 
                                             alt="{{ $produit->designation }}" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px; border-radius: 5px;">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $produit->designation }}</td>
                                <td>
                                    @if($produit->fournisseur)
                                        {{ $produit->fournisseur->nom }}
                                    @else
                                        <span class="text-muted">Non défini</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $produit->sousCategorie->categorie->nom }}</small><br>
                                    <strong>{{ $produit->sousCategorie->nom }}</strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2">{{ $produit->quantite_stock }}</span>
                                        @if($produit->quantite_stock < 10)
                                            <span class="badge bg-danger">Faible</span>
                                        @elseif($produit->quantite_stock < 50)
                                            <span class="badge bg-warning">Moyen</span>
                                        @else
                                            <span class="badge bg-success">Bon</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ number_format($produit->prix_unitaire, 0, ',', ' ') }} FCFA</strong>
                                </td>
                                <td>
                                    @if($produit->archive)
                                        <span class="badge bg-danger">Archivé</span>
                                    @else
                                        <span class="badge bg-success">Actif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('produits.edit', $produit->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="{{ route('produits.show', $produit->id) }}" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form action="{{ route('produits.archive', $produit->id) }}" 
                                              method="POST" 
                                              style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                @if($produit->archive)
                                                    <i class="bi bi-archive"></i> Désarchiver
                                                @else
                                                    <i class="bi bi-archive"></i> Archiver
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
