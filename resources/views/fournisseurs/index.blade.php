@extends('layouts.app')

@section('title', 'Gestion des Fournisseurs')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Gestion des Fournisseurs</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFournisseurModal">
                <i class="bi bi-plus-circle"></i> Nouveau Fournisseur
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
                                <th>N° Fournisseur</th>
                                <th>Nom</th>
                                <th>Adresse</th>
                                <th>Commandes</th>
                                <th>Dette</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fournisseurs as $fournisseur)
                            <tr>
                                <td>
                                    <strong>{{ $fournisseur->numero }}</strong>
                                </td>
                                <td>{{ $fournisseur->nom }}</td>
                                <td>{{ Str::limit($fournisseur->adresse, 50) }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $fournisseur->commandes->count() }}</span>
                                </td>
                                <td>
                                    @php
                                        $dette = $fournisseur->detteTotale();
                                    @endphp
                                    @if($dette > 0)
                                        <span class="text-danger fw-bold">
                                            {{ number_format($dette, 0, ',', ' ') }} FCFA
                                        </span>
                                    @else
                                        <span class="text-success">Aucune dette</span>
                                    @endif
                                </td>
                                <td>
                                    @if($fournisseur->archive)
                                        <span class="badge bg-danger">Archivé</span>
                                    @else
                                        <span class="badge bg-success">Actif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editFournisseurModal"
                                                data-id="{{ $fournisseur->id }}"
                                                data-numero="{{ $fournisseur->numero }}"
                                                data-nom="{{ $fournisseur->nom }}"
                                                data-adresse="{{ $fournisseur->adresse }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="{{ route('fournisseurs.show', $fournisseur->id) }}" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-success"
                                                onclick="showDetteDetails({{ $fournisseur->id }})">
                                            <i class="bi bi-cash-coin"></i>
                                        </button>
                                        <form action="{{ route('fournisseurs.archive', $fournisseur->id) }}" 
                                              method="POST" 
                                              style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                @if($fournisseur->archive)
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
<div class="modal fade" id="createFournisseurModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau Fournisseur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('fournisseurs.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="numero" class="form-label">Numéro fournisseur *</label>
                        <input type="text" class="form-control" id="numero" name="numero" required>
                        <small class="text-muted">Ex: FOU-001</small>
                    </div>
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse *</label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="3" required></textarea>
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
<div class="modal fade" id="editFournisseurModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le Fournisseur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editFournisseurForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_numero" class="form-label">Numéro fournisseur *</label>
                        <input type="text" class="form-control" id="edit_numero" name="numero" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_nom" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="edit_nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_adresse" class="form-label">Adresse *</label>
                        <textarea class="form-control" id="edit_adresse" name="adresse" rows="3" required></textarea>
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

<!-- Modal des détails de dette -->
<div class="modal fade" id="detteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la dette</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detteContent">
                    <!-- Contenu chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Gérer l'ouverture du modal d'édition
$('#editFournisseurModal').on('show.bs.modal', function(event) {
    const button = $(event.relatedTarget);
    const id = button.data('id');
    const numero = button.data('numero');
    const nom = button.data('nom');
    const adresse = button.data('adresse');
    
    const modal = $(this);
    modal.find('#edit_numero').val(numero);
    modal.find('#edit_nom').val(nom);
    modal.find('#edit_adresse').val(adresse);
    modal.find('#editFournisseurForm').attr('action', '/fournisseurs/' + id);
});

// Fonction pour afficher les détails de la dette
function showDetteDetails(fournisseurId) {
    $.ajax({
        url: '/api/fournisseurs/' + fournisseurId + '/dette',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
            'Accept': 'application/json'
        },
        success: function(response) {
            $.ajax({
                url: '/api/fournisseurs/' + fournisseurId,
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                    'Accept': 'application/json'
                },
                success: function(fournisseurDetails) {
                    let html = `
                        <h6>${fournisseurDetails.nom} (${fournisseurDetails.numero})</h6>
                        <div class="alert alert-info">
                            <h5 class="mb-0">Dette totale: ${response.dette_formatee}</h5>
                        </div>
                        
                        <h6 class="mt-4">Commandes avec dette:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>N° Commande</th>
                                        <th>Date</th>
                                        <th>Montant total</th>
                                        <th>Montant payé</th>
                                        <th>Montant restant</th>
                                        <th>État</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    
                    fournisseurDetails.commandes.forEach(commande => {
                        if (commande.etat !== 'cloturee' && commande.etat !== 'annule') {
                            // Calculer le montant restant
                            const montantVerse = commande.versements?.reduce((sum, v) => sum + v.montant, 0) || 0;
                            const montantRestant = commande.montant_total - montantVerse;
                            
                            if (montantRestant > 0) {
                                html += `
                                    <tr>
                                        <td>CMD-${commande.id.toString().padStart(6, '0')}</td>
                                        <td>${new Date(commande.date_commande).toLocaleDateString('fr-FR')}</td>
                                        <td>${formatMoney(commande.montant_total)} FCFA</td>
                                        <td>${formatMoney(montantVerse)} FCFA</td>
                                        <td class="text-danger fw-bold">${formatMoney(montantRestant)} FCFA</td>
                                        <td><span class="badge ${getEtatClass(commande.etat)}">${getEtatText(commande.etat)}</span></td>
                                    </tr>`;
                            }
                        }
                    });
                    
                    html += `</tbody></table></div>`;
                    
                    $('#detteContent').html(html);
                    $('#detteModal').modal('show');
                }
            });
        }
    });
}

function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR').format(amount);
}

function getEtatClass(etat) {
    switch(etat) {
        case 'brouillon': return 'badge-warning';
        case 'validee': return 'badge-en_cours';
        case 'recue': return 'badge-livre';
        case 'cloturee': return 'badge-paye';
        case 'annule': return 'badge-annule';
        default: return 'badge-secondary';
    }
}

function getEtatText(etat) {
    switch(etat) {
        case 'brouillon': return 'Brouillon';
        case 'validee': return 'Validée';
        case 'recue': return 'Reçue';
        case 'cloturee': return 'Clôturée';
        case 'annule': return 'Annulé';
        default: return etat;
    }
}
</script>
@endpush
@endsection
