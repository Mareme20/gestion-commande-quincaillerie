@extends('layouts.app')

@section('title', 'Sous-catégories')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Sous-catégories</h1>
            <a href="{{ route('sous-categories.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nouvelle sous-catégorie
            </a>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('sous-categories.index') }}" class="row g-3">
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <label for="archive" class="form-label">Statut</label>
                        <select class="form-select" id="archive" name="archive">
                            <option value="">Tous les statuts</option>
                            <option value="0" {{ request('archive') === '0' ? 'selected' : '' }}>Actives</option>
                            <option value="1" {{ request('archive') === '1' ? 'selected' : '' }}>Archivées</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Filtrer
                            </button>
                            <a href="{{ route('sous-categories.index') }}" class="btn btn-secondary">
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
                @if($sousCategories->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Catégorie parente</th>
                                <th>Description</th>
                                <th>Produits</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sousCategories as $sousCategorie)
                            <tr>
                                <td>
                                    <strong>{{ $sousCategorie->nom }}</strong>
                                </td>
                                <td>
                                    <a href="{{ route('categories.show', $sousCategorie->categorie_id) }}">
                                        {{ $sousCategorie->categorie->nom }}
                                    </a>
                                </td>
                                <td>{{ Str::limit($sousCategorie->description, 50) }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $sousCategorie->produits->count() }}</span>
                                </td>
                                <td>
                                    @if($sousCategorie->archive)
                                        <span class="badge bg-danger">Archivée</span>
                                    @else
                                        <span class="badge bg-success">Active</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('sous-categories.show', $sousCategorie) }}" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('sous-categories.edit', $sousCategorie) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('sous-categories.archive', $sousCategorie) }}" 
                                              method="POST" 
                                              style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                @if($sousCategorie->archive)
                                                    <i class="bi bi-archive"></i> Désarchiver
                                                @else
                                                    <i class="bi bi-archive"></i> Archiver
                                                @endif
                                            </button>
                                        </form>
                                        <form action="{{ route('sous-categories.destroy', $sousCategorie) }}" 
                                              method="POST" 
                                              style="display: inline;"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette sous-catégorie?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
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
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-tags" style="font-size: 3rem;"></i>
                    <p class="mt-2">Aucune sous-catégorie trouvée</p>
                    <a href="{{ route('sous-categories.create') }}" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle"></i> Créer une sous-catégorie
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection