@extends('layouts.app')

@section('title', 'Gestion des Catégories')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Gestion des Catégories</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                <i class="bi bi-plus-circle"></i> Nouvelle Catégorie
            </button>
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
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Sous-catégories</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $categorie)
                            <tr>
                                <td>{{ $categorie->id }}</td>
                                <td>
                                    <strong>{{ $categorie->nom }}</strong>
                                </td>
                                <td>{{ Str::limit($categorie->description, 50) }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $categorie->sousCategories->count() }}</span>
                                </td>
                                <td>
                                    @if($categorie->archive)
                                        <span class="badge bg-danger">Archivée</span>
                                    @else
                                        <span class="badge bg-success">Active</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCategoryModal"
                                                data-id="{{ $categorie->id }}"
                                                data-nom="{{ $categorie->nom }}"
                                                data-description="{{ $categorie->description }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="viewSubcategories({{ $categorie->id }})">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <form action="{{ route('categories.archive', $categorie->id) }}" 
                                              method="POST" 
                                              style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                @if($categorie->archive)
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

<!-- Modal de création -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
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

<!-- Modal d'édition -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la Catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm" method="POST">
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

<!-- Modal des sous-catégories -->
<div class="modal fade" id="subcategoriesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sous-catégories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="subcategoriesContent">
                    <!-- Contenu chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Gérer l'ouverture du modal d'édition
$('#editCategoryModal').on('show.bs.modal', function(event) {
    const button = $(event.relatedTarget);
    const id = button.data('id');
    const nom = button.data('nom');
    const description = button.data('description');
    
    const modal = $(this);
    modal.find('#edit_nom').val(nom);
    modal.find('#edit_description').val(description);
    modal.find('#editCategoryForm').attr('action', '/categories/' + id);
});

// Fonction pour afficher les sous-catégories
function viewSubcategories(categorieId) {
    $.ajax({
        url: '/api/categories/' + categorieId,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
            'Accept': 'application/json'
        },
        success: function(response) {
            let html = `
                <h6>${response.nom} - Sous-catégories</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            response.sous_categories.forEach(sub => {
                html += `
                    <tr>
                        <td>${sub.nom}</td>
                        <td>${sub.description || '-'}</td>
                        <td>${sub.archive ? '<span class="badge bg-danger">Archivée</span>' : '<span class="badge bg-success">Active</span>'}</td>
                    </tr>`;
            });
            
            html += `</tbody></table></div>`;
            
            $('#subcategoriesContent').html(html);
            $('#subcategoriesModal').modal('show');
        }
    });
}
</script>
@endpush
@endsection