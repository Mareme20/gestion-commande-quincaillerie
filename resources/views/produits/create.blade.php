@extends('layouts.app')

@section('title', 'Nouveau Produit')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Nouveau Produit</h1>
            <a href="{{ route('produits.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('produits.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">Code produit *</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                   id="code" name="code" value="{{ old('code') }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="designation" class="form-label">Désignation *</label>
                            <input type="text" class="form-control @error('designation') is-invalid @enderror" 
                                   id="designation" name="designation" value="{{ old('designation') }}" required>
                            @error('designation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fournisseur_id" class="form-label">Fournisseur *</label>
                            <select class="form-select @error('fournisseur_id') is-invalid @enderror"
                                    id="fournisseur_id" name="fournisseur_id" required>
                                <option value="">Sélectionnez un fournisseur</option>
                                @foreach($fournisseurs as $fournisseur)
                                    <option value="{{ $fournisseur->id }}"
                                        {{ old('fournisseur_id') == $fournisseur->id ? 'selected' : '' }}>
                                        {{ $fournisseur->nom }} ({{ $fournisseur->numero }})
                                    </option>
                                @endforeach
                            </select>
                            @error('fournisseur_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="categorie_id" class="form-label">Catégorie *</label>
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
                        
                        <div class="col-md-6 mb-3">
                            <label for="sous_categorie_id" class="form-label">Sous-catégorie *</label>
                            <select class="form-select @error('sous_categorie_id') is-invalid @enderror" 
                                    id="sous_categorie_id" name="sous_categorie_id" required>
                                <option value="">Sélectionnez d'abord une catégorie</option>
                            </select>
                            @error('sous_categorie_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="quantite_stock" class="form-label">Quantité en stock *</label>
                            <input type="number" step="0.01" class="form-control @error('quantite_stock') is-invalid @enderror" 
                                   id="quantite_stock" name="quantite_stock" value="{{ old('quantite_stock', 0) }}" required>
                            @error('quantite_stock')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="prix_unitaire" class="form-label">Prix unitaire (FCFA) *</label>
                            <input type="number" step="0.01" class="form-control @error('prix_unitaire') is-invalid @enderror" 
                                   id="prix_unitaire" name="prix_unitaire" value="{{ old('prix_unitaire') }}" required>
                            @error('prix_unitaire')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="image" class="form-label">Image du produit</label>
                            <input type="file" class="form-control @error('image') is-invalid @enderror" 
                                   id="image" name="image" accept="image/*">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Taille max: 2MB, Formats: JPG, PNG, GIF</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (optionnel)</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Enregistrer le produit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Aperçu du produit</h6>
            </div>
            <div class="card-body text-center">
                <div id="imagePreview" class="mb-3" style="display: none;">
                    <img id="previewImage" class="img-fluid rounded" 
                         style="max-height: 200px; object-fit: cover;">
                </div>
                <div id="noImage" class="mb-3">
                    <div class="bg-light d-flex align-items-center justify-content-center" 
                         style="height: 200px; border-radius: 10px;">
                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <div id="productPreview">
                    <h5 id="previewDesignation" class="text-muted">Désignation</h5>
                    <p class="text-muted mb-1">Code: <span id="previewCode">---</span></p>
                    <p class="text-muted mb-1">Stock: <span id="previewStock">0</span></p>
                    <h4 id="previewPrix" class="text-primary mb-0">0 FCFA</h4>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Charger les sous-catégories selon la catégorie sélectionnée
    $('#categorie_id').change(function() {
        const categorieId = $(this).val();
        
        if (categorieId) {
            $.ajax({
                url: '/api/categories/' + categorieId,
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    const sousCategoriesSelect = $('#sous_categorie_id');
                    sousCategoriesSelect.empty();
                    sousCategoriesSelect.append('<option value="">Sélectionnez une sous-catégorie</option>');
                    
                    response.sous_categories.forEach(function(sousCategorie) {
                        if (!sousCategorie.archive) {
                            sousCategoriesSelect.append(
                                `<option value="${sousCategorie.id}">${sousCategorie.nom}</option>`
                            );
                        }
                    });
                }
            });
        } else {
            $('#sous_categorie_id').empty().append('<option value="">Sélectionnez d\'abord une catégorie</option>');
        }
    });
    
    // Aperçu de l'image
    $('#image').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImage').attr('src', e.target.result);
                $('#imagePreview').show();
                $('#noImage').hide();
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Mise à jour de l'aperçu en temps réel
    $('#designation').on('input', function() {
        $('#previewDesignation').text($(this).val() || 'Désignation');
    });
    
    $('#code').on('input', function() {
        $('#previewCode').text($(this).val() || '---');
    });
    
    $('#quantite_stock').on('input', function() {
        $('#previewStock').text($(this).val() || '0');
    });
    
    $('#prix_unitaire').on('input', function() {
        const prix = parseFloat($(this).val()) || 0;
        $('#previewPrix').text(new Intl.NumberFormat('fr-FR').format(prix) + ' FCFA');
    });
});
</script>
@endpush
@endsection
