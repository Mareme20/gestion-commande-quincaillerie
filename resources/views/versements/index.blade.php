@extends('layouts.app')

@section('title', 'Gestion des Versements')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Gestion des Versements</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createVersementModal">
                <i class="bi bi-plus-circle"></i> Nouveau Versement
            </button>
        </div>
    </div>
</div>

<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Versements aujourd'hui</h6>
                        <h4 class="mb-0">{{ number_format($stats['versements_jour'], 0, ',', ' ') }} FCFA</h4>
                    </div>
                    <i class="bi bi-calendar-check" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Versements ce mois</h6>
                        <h4 class="mb-0">{{ number_format($stats['versements_mois'], 0, ',', ' ') }} FCFA</h4>
                    </div>
                    <i class="bi bi-cash-stack" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total versé</h6>
                        <h4 class="mb-0">{{ number_format($stats['versements_total'], 0, ',', ' ') }} FCFA</h4>
                    </div>
                    <i class="bi bi-currency-exchange" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('versements.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="date_debut" class="form-label">Date début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" 
                               value="{{ request('date_debut') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="date_fin" class="form-label">Date fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" 
                               value="{{ request('date_fin') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="commande_id" class="form-label">Commande</label>
                        <select class="form-select" id="commande_id" name="commande_id">
                            <option value="">Toutes les commandes</option>
                            @foreach($commandes as $commande)
                                @if($commande->etat != 'annule')
                                <option value="{{ $commande->id }}" 
                                    {{ request('commande_id') == $commande->id ? 'selected' : '' }}>
                                    CMD-{{ str_pad($commande->id, 6, '0', STR_PAD_LEFT) }} - {{ $commande->fournisseur->nom }}
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Filtrer
                            </button>
                            <a href="{{ route('versements.index') }}" class="btn btn-secondary">
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
                                <th>N° Versement</th>
                                <th>Commande</th>
                                <th>Fournisseur</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versements as $versement)
                            <tr>
                                <td>
                                    <strong>{{ $versement->numero_versement }}</strong>
                                </td>
                                <td>
                                    CMD-{{ str_pad($versement->commande_id, 6, '0', STR_PAD_LEFT) }}
                                    <br>
                                    <small class="text-muted">
                                        Total: {{ number_format($versement->commande->montant_total, 0, ',', ' ') }} FCFA
                                    </small>
                                </td>
                                <td>{{ $versement->commande->fournisseur->nom }}</td>
                                <td>{{ $versement->date_versement->format('d/m/Y') }}</td>
                                <td>
                                    <strong class="text-success">
                                        {{ number_format($versement->montant, 0, ',', ' ') }} FCFA
                                    </strong>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info"
                                                onclick="showVersementDetails({{ $versement->id }})">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editVersementModal"
                                                data-id="{{ $versement->id }}"
                                                data-montant="{{ $versement->montant }}"
                                                data-date="{{ $versement->date_versement->format('Y-m-d') }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('versements.destroy', $versement->id) }}" 
                                              method="POST" 
                                              style="display: inline;"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce versement?')">
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
            </div>
        </div>
    </div>
</div>

<!-- Modal de création -->
<div class="modal fade" id="createVersementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau Versement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('versements.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="commande_id" class="form-label">Commande *</label>
                        <select class="form-select" id="commande_id" name="commande_id" required>
                            <option value="">Sélectionnez une commande</option>
                            @foreach($commandes as $commande)
                                @if($commande->etat != 'annule' && $commande->etat != 'cloturee')
                                    @php
                                        $montantVerse = $commande->versements->sum('montant');
                                        $montantRestant = $commande->montant_total - $montantVerse;
                                    @endphp
                                    @if($montantRestant > 0)
                                    <option value="{{ $commande->id }}" 
                                            data-montant-restant="{{ $montantRestant }}">
                                        CMD-{{ str_pad($commande->id, 6, '0', STR_PAD_LEFT) }} - 
                                        {{ $commande->fournisseur->nom }} - 
                                        Reste: {{ number_format($montantRestant, 0, ',', ' ') }} FCFA
                                    </option>
                                    @endif
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant (FCFA) *</label>
                        <input type="number" step="0.01" class="form-control" id="montant" name="montant" required>
                        <small class="text-muted" id="montant-max"></small>
                    </div>
                    <div class="mb-3">
                        <label for="date_versement" class="form-label">Date du versement *</label>
                        <input type="date" class="form-control" id="date_versement" name="date_versement" 
                               value="{{ date('Y-m-d') }}" required>
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
<div class="modal fade" id="editVersementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le Versement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editVersementForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_montant" class="form-label">Montant (FCFA) *</label>
                        <input type="number" step="0.01" class="form-control" id="edit_montant" name="montant" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_date_versement" class="form-label">Date du versement *</label>
                        <input type="date" class="form-control" id="edit_date_versement" name="date_versement" required>
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

<!-- Modal des détails -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du versement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailsContent">
                    <!-- Contenu chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Gérer l'ouverture du modal d'édition
$('#editVersementModal').on('show.bs.modal', function(event) {
    const button = $(event.relatedTarget);
    const id = button.data('id');
    const montant = button.data('montant');
    const date = button.data('date');
    
    const modal = $(this);
    modal.find('#edit_montant').val(montant);
    modal.find('#edit_date_versement').val(date);
    modal.find('#editVersementForm').attr('action', '/versements/' + id);
});

// Gérer la sélection de commande pour le montant maximum
$('#commande_id').change(function() {
    const selectedOption = $(this).find('option:selected');
    const montantRestant = selectedOption.data('montant-restant');
    
    if (montantRestant) {
        $('#montant').attr('max', montantRestant);
        $('#montant-max').text(`Maximum: ${new Intl.NumberFormat('fr-FR').format(montantRestant)} FCFA`);
    } else {
        $('#montant').removeAttr('max');
        $('#montant-max').text('');
    }
});

// Limiter le montant saisi
$('#montant').on('input', function() {
    const max = parseFloat($(this).attr('max'));
    if (max) {
        const valeur = parseFloat($(this).val()) || 0;
        if (valeur > max) {
            $(this).val(max);
            toastr.warning('Le montant ne peut pas dépasser le montant restant');
        }
    }
});

// Fonction pour afficher les détails du versement
function showVersementDetails(versementId) {
    $.ajax({
        url: '/api/versements/' + versementId,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
            'Accept': 'application/json'
        },
        success: function(response) {
            const versement = response;
            const commande = versement.commande;
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informations du versement</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>N° Versement:</td>
                                <td><strong>${versement.numero_versement}</strong></td>
                            </tr>
                            <tr>
                                <td>Date:</td>
                                <td>${new Date(versement.date_versement).toLocaleDateString('fr-FR')}</td>
                            </tr>
                            <tr>
                                <td>Montant:</td>
                                <td class="text-success fw-bold">${formatMoney(versement.montant)} FCFA</td>
                            </tr>
                            <tr>
                                <td>Date création:</td>
                                <td>${new Date(versement.created_at).toLocaleString('fr-FR')}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Informations de la commande</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>N° Commande:</td>
                                <td><strong>CMD-${commande.id.toString().padStart(6, '0')}</strong></td>
                            </tr>
                            <tr>
                                <td>Fournisseur:</td>
                                <td>${commande.fournisseur.nom}</td>
                            </tr>
                            <tr>
                                <td>Montant total:</td>
                                <td>${formatMoney(commande.montant_total)} FCFA</td>
                            </tr>
                            <tr>
                                <td>État:</td>
                                <td><span class="badge ${getEtatClass(commande.etat)}">${getEtatText(commande.etat)}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>`;
            
            $('#detailsContent').html(html);
            $('#detailsModal').modal('show');
        }
    });
}

function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR').format(amount);
}

function getEtatClass(etat) {
    switch(etat) {
        case 'brouillon': return 'badge-warning';
        case 'validee': return 'badge-primary';
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
