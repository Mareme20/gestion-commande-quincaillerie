@extends('layouts.app')

@section('title', 'Nouvelle Sous-catégorie')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Nouvelle Sous-catégorie</h1>
            <a href="{{ route('sous-categories.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('sous-categories.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="categorie_id" class="form-label">Catégorie parente *</label>
                        <select class="form-select @error('categorie_id') is-invalid @enderror" 
                                id="categorie_id" name="categorie_id" required>
                            <option value="">Sélectionnez une catégorie</option>
                            @foreach($categories as $categorie)
                                <option value="{{ $categorie->id }}" 
                                    {{ old('categorie_id') == $categorie->id ? 'selected' : '' }}>
                                    {{ $categorie->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('categorie_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom de la sous-catégorie *</label>
                        <input type="text" class="form-control @error('nom') is-invalid @enderror" 
                               id="nom" name="nom" value="{{ old('nom') }}" required>
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Exemple: Pour la catégorie "Fer" → "Fer 8", "Fer 12", "Fer 16"</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (optionnel)</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="4">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Créer la sous-catégorie
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Conseils</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> À propos des sous-catégories</h6>
                    <ul class="mb-0">
                        <li>Les sous-catégories organisent les produits au sein d'une catégorie</li>
                        <li>Exemple: Catégorie "Fer" → Sous-catégories "Fer 8", "Fer 12", "Fer 16"</li>
                        <li>Chaque produit doit appartenir à une sous-catégorie</li>
                        <li>Une sous-catégorie ne peut appartenir qu'à une seule catégorie</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6><i class="bi bi-exclamation-triangle"></i> Important</h6>
                    <p class="mb-0">
                        Vous ne pourrez pas archiver une sous-catégorie si elle contient des produits actifs.
                        Vous ne pourrez pas la supprimer si elle contient des produits.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Catégories disponibles -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Catégories disponibles</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @foreach($categories as $categorie)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $categorie->nom }}
                        <span class="badge bg-info">{{ $categorie->sousCategories->count() }} sous-cat.</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection