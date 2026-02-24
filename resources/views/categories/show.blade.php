@extends('layouts.app')

@section('title', $categorie->nom)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">{{ $categorie->nom }}</h1>
                @if($categorie->description)
                    <p class="text-muted mb-0">{{ $categorie->description }}</p>
                @endif
            </div>
            <div>
                <a href="{{ route('categories.edit', $categorie) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
                <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Sous-catégories -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Sous-catégories</h6>
                <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createSousCategorieModal">
                    <i class="bi bi-plus-circle"></i> Ajouter
                </a>
            </div>
            <div class="card-body">
                @if($categorie->sousCategories->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Produits</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categorie->sousCategories as $sousCategorie)
                            <tr>
                                <td>
                                    <strong>{{ $sousCategorie->nom }}</strong>
                                </td>
                                <td>{{ Str::limit($sousCategorie->description, 50) }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $sousCategorie->produits_count }}</span>
                                </td>
                                <td>
                                    @if($sousCategorie->archive)
                                        <span class="badge bg-danger">Archivée</span>
                                    @else
                                        <span class="badge bg-success">Active</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editSousCategorieModal"
                                                data-id="{{ $sousCategorie->id }}"
                                                data-nom="{{ $sousCategorie->nom }}"
                                                data-description="{{ $sousCategorie->description }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('sous-categories.destroy', $sousCategorie) }}" 
                                              method="POST" 
                                              style="display: inline;"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette sous-catégorie?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-tags" style="font-size: 3rem;"></i>
                    <p class="mt-2">Aucune sous-catégorie pour cette catégorie</p>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Produits récents -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Produits récents</h6>
                <a href="{{ route('produits.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle"></i> Nouveau produit
                </a>
            </div>
            <div class="card-body">
                @if($categorie->produits->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Produit</th>
                                <th>Sous-catégorie</th>
                                <th>Stock</th>
                                <th>Prix</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categorie->produits as $produit)
                            <tr>
                                <td>{{ $produit->code }}</td>
                                <td>
                                    <a href="{{ route('produits.show', $produit) }}">
                                        {{ $produit->designation }}
                                    </a>
                                </td>
                                <td>{{ $produit->sousCategorie->nom }}</td>
                                <td>
                                    <span class="badge {{ $produit->quantite_stock < 10 ? 'bg-danger' : 
                                                         ($produit->quantite_stock < 50 ? 'bg-warning' : 'bg-success') }}">
                                        {{ $produit->quantite_stock }}
                                    </span>
                                </td>
                                <td>{{ number_format($produit->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-box" style="font-size: 3rem;"></i>
                    <p class="mt-2">Aucun produit dans cette catégorie</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Informations -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informations</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Statut:</td>
                        <td class="text-end">
                            @if($categorie->archive)
                                <span class="badge bg-danger">Archivée</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Sous-catégories:</td>
                        <td class="text-end">
                            <span class="badge bg-info">{{ $categorie->sousCategories->count() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td>Produits totaux:</td>
                        <td class="text-end">
                            <span class="badge bg-primary">{{ $categorie->produits->count() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td>Produits actifs:</td>
                        <td class="text-end">
                            {{ $categorie->produits->where('archive', false)->count() }}
                        </td>
                    </tr>
                    <tr>
                        <td>Créée le:</td>
                        <td class="text-end">{{ $categorie->created_at->format('d/m/Y à H:i') }}</td>
                    </tr>
                    <tr>
                        <td>Dernière modification:</td>
                        <td class="text-end">{{ $categorie->updated_at->format('d/m/Y à H:i') }}</td>
                    </tr>
                </table>
                
                @if($categorie->produits->where('archive', false)->count() == 0)
                <form action="{{ route('categories.archive', $categorie) }}" method="POST" 
                      class="d-grid mt-3"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir archiver cette catégorie?')">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-archive"></i> 
                        {{ $categorie->archive ? 'Désarchiver la catégorie' : 'Archiver la catégorie' }}
                    </button>
                </form>
                @endif
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Actions rapides</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('categories.edit', $categorie) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Modifier la catégorie
                    </a>
                    <a href="{{ route('produits.create') }}?categorie_id={{ $categorie->id }}" 
                       class="btn btn-outline-success">
                        <i class="bi bi-plus-circle"></i> Ajouter un produit
                    </a>
                    @if($categorie->produits->where('archive', false)->count() == 0)
                    <form action="{{ route('categories.destroy', $categorie) }}" method="POST"
                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie? Cette action est irréversible.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash"></i> Supprimer la catégorie
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal création sous-catégorie -->
<div class="modal fade" id="createSousCategorieModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Sous-catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('sous-categories.store') }}" method="POST">
                @csrf
                <input type="hidden" name="categorie_id" value="{{ $categorie->id }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal édition sous-catégorie -->
<div class="modal fade" id="editSousCategorieModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la Sous-catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSousCategorieForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nom" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="edit_nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Gérer l'ouverture du modal d'édition
$('#editSousCategorieModal').on('show.bs.modal', function(event) {
    const button = $(event.relatedTarget);
    const id = button.data('id');
    const nom = button.data('nom');
    const description = button.data('description');
    
    const modal = $(this);
    modal.find('#edit_nom').val(nom);
    modal.find('#edit_description').val(description);
    modal.find('#editSousCategorieForm').attr('action', '/sous-categories/' + id);
});
</script>
@endpush
@endsection