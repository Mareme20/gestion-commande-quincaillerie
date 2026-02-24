  @extends('layouts.app')

@section('title', 'Gestion des Commandes')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Gestion des Commandes</h1>
            <div>
                <a href="{{ route('commandes.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nouvelle Commande
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
                <form method="GET" action="{{ route('commandes.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="etat" class="form-label">État</label>
                        <select class="form-select" id="etat" name="etat">
                            <option value="">Tous les états</option>
                            <option value="brouillon" {{ request('etat') == 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                            <option value="validee" {{ request('etat') == 'validee' ? 'selected' : '' }}>Validée</option>
                            <option value="recue" {{ request('etat') == 'recue' ? 'selected' : '' }}>Reçue</option>
                            <option value="cloturee" {{ request('etat') == 'cloturee' ? 'selected' : '' }}>Clôturée</option>
                            <option value="annule" {{ request('etat') == 'annule' ? 'selected' : '' }}>Annulé</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fournisseur_id" class="form-label">Fournisseur</label>
                        <select class="form-select" id="fournisseur_id" name="fournisseur_id">
                            <option value="">Tous les fournisseurs</option>
                            @foreach($fournisseurs as $fournisseur)
                                <option value="{{ $fournisseur->id }}" 
                                    {{ request('fournisseur_id') == $fournisseur->id ? 'selected' : '' }}>
                                    {{ $fournisseur->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
                    <div class="col-12 d-flex justify-content-end">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Filtrer
                            </button>
                            <a href="{{ route('commandes.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Brouillon</h6>
                        <h4 class="mb-0">{{ $stats['brouillon'] }}</h4>
                    </div>
                    <i class="bi bi-clock-history" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Validées</h6>
                        <h4 class="mb-0">{{ $stats['validee'] }}</h4>
                    </div>
                    <i class="bi bi-truck" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Reçues</h6>
                        <h4 class="mb-0">{{ $stats['recue'] }}</h4>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Clôturées</h6>
                        <h4 class="mb-0">{{ $stats['cloturee'] }}</h4>
                    </div>
                    <i class="bi bi-x-circle" style="font-size: 2rem;"></i>
                </div>
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
                                <th>N° Commande</th>
                                <th>Fournisseur</th>
                                <th>Date Commande</th>
                                <th>Livraison prévue</th>
                                <th>Montant total</th>
                                <th>État</th>
                                <th>Paiement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commandes as $commande)
                            @php
                                $montantVerse = $commande->versements->sum('montant');
                                $montantRestant = $commande->montant_total - $montantVerse;
                                $pourcentagePaye = $commande->montant_total > 0 ? 
                                    ($montantVerse / $commande->montant_total) * 100 : 0;
                            @endphp
                            <tr>
                                <td>
                                    <strong>CMD-{{ str_pad($commande->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                </td>
                                <td>{{ $commande->fournisseur->nom }}</td>
                                <td>{{ $commande->date_commande->format('d/m/Y') }}</td>
                                <td>
                                    {{ $commande->date_livraison_prevue->format('d/m/Y') }}
                                    @if($commande->date_livraison_reelle)
                                        <br>
                                        <small class="text-success">Livré le: {{ $commande->date_livraison_reelle->format('d/m/Y') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($commande->montant_total, 0, ',', ' ') }} FCFA</strong>
                                </td>
                                <td>
                                    <span class="badge badge-etat {{ $commande->etatBadgeClass() }}">
                                        {{ $commande->etatLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar {{ $pourcentagePaye == 100 ? 'bg-success' : 
                                                                   ($pourcentagePaye > 0 ? 'bg-warning' : 'bg-danger') }}" 
                                             role="progressbar" 
                                             style="width: {{ $pourcentagePaye }}%"
                                             aria-valuenow="{{ $pourcentagePaye }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            {{ round($pourcentagePaye) }}%
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        Reste: {{ number_format($montantRestant, 0, ',', ' ') }} FCFA
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('commandes.show', $commande->id) }}" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($commande->etat === 'brouillon')
                                            <form action="{{ route('commandes.valider', $commande->id) }}"
                                                  method="POST"
                                                  style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Valider">
                                                    <i class="bi bi-check2-circle"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('commandes.edit', $commande->id) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
                                        @if(in_array($commande->etat, ['brouillon', 'validee']))
                                            <form action="{{ route('commandes.annuler', $commande->id) }}" 
                                                  method="POST" 
                                                  style="display: inline;"
                                                  onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette commande?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if(Auth::user()->isResponsablePaiement() && $montantRestant > 0 && $commande->etat === 'recue' && $commande->date_livraison_reelle)
                                            <button class="btn btn-sm btn-outline-success"
                                                    onclick="showPaiementModal({{ $commande->id }})">
                                                <i class="bi bi-cash-coin"></i>
                                            </button>
                                        @endif
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

<!-- Modal de paiement -->
<div class="modal fade" id="paiementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enregistrer un paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paiementForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Commande</label>
                        <p class="form-control-plaintext" id="modalCommandeInfo"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Montant restant</label>
                        <p class="form-control-plaintext text-danger fw-bold" id="modalMontantRestant"></p>
                    </div>
                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant à payer *</label>
                        <input type="number" step="0.01" class="form-control" id="montant" name="montant" required>
                        <small class="text-muted" id="montantMax"></small>
                    </div>
                    <div class="mb-3">
                        <label for="date_versement" class="form-label">Date du paiement *</label>
                        <input type="date" class="form-control" id="date_versement" name="date_versement" 
                               value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="genererEchelonne" name="generer_echelonne">
                        <label class="form-check-label" for="genererEchelonne">
                            Générer 3 versements échelonnés (5 jours d'intervalle)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Enregistrer le paiement</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showPaiementModal(commandeId) {
    $.ajax({
        url: '/api/commandes/' + commandeId,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
            'Accept': 'application/json'
        },
        success: function(response) {
            const commande = response.commande;
            const montantRestant = response.montant_restant;
            
            $('#modalCommandeInfo').text(
                `CMD-${commande.id.toString().padStart(6, '0')} - ${commande.fournisseur.nom}`
            );
            $('#modalMontantRestant').text(
                new Intl.NumberFormat('fr-FR').format(montantRestant) + ' FCFA'
            );
            $('#montant').attr('max', montantRestant);
            $('#montantMax').text(`Maximum: ${new Intl.NumberFormat('fr-FR').format(montantRestant)} FCFA`);
            
            $('#paiementForm').attr('action', '/versements');
            $('#paiementForm').append(`<input type="hidden" name="commande_id" value="${commandeId}">`);
            
            $('#paiementModal').modal('show');
        }
    });
}

// Limiter le montant saisi
$('#montant').on('input', function() {
    const max = parseFloat($(this).attr('max'));
    const valeur = parseFloat($(this).val());
    
    if (valeur > max) {
        $(this).val(max);
        toastr.warning('Le montant ne peut pas dépasser le montant restant');
    }
});

// Soumission du formulaire de paiement
$('#paiementForm').submit(function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const url = $(this).attr('action');
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            toastr.success('Paiement enregistré avec succès');
            $('#paiementModal').modal('hide');
            // Recharger la page après 1 seconde
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                $.each(xhr.responseJSON.errors, function(key, value) {
                    toastr.error(value[0]);
                });
            } else {
                toastr.error('Erreur lors de l\'enregistrement du paiement');
            }
        }
    });
});
</script>
@endpush
@endsection
