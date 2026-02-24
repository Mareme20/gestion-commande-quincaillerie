@extends('layouts.app')

@section('title', 'Nouvelle Commande')

@push('styles')
<style>
    .produit-item {
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
        background: #f8f9fa;
    }
    .produit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .produit-content {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    .form-group-compact {
        flex: 1;
        min-width: 200px;
    }
    .remove-produit {
        color: #dc3545;
        cursor: pointer;
    }
    #produits-container {
        max-height: 500px;
        overflow-y: auto;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Nouvelle Commande</h1>
            <a href="{{ route('commandes.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form id="commandeForm" action="{{ route('commandes.store') }}" method="POST">
                    @csrf
                    
                    <!-- Informations de base -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fournisseur_id" class="form-label">Fournisseur *</label>
                                <select class="form-select @error('fournisseur_id') is-invalid @enderror" 
                                        id="fournisseur_id" name="fournisseur_id" required onchange="filtrerProduitsParFournisseur()">
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
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="date_commande" class="form-label">Date de commande *</label>
                                <input type="date" class="form-control @error('date_commande') is-invalid @enderror" 
                                       id="date_commande" name="date_commande" 
                                       value="{{ old('date_commande', date('Y-m-d')) }}" required>
                                @error('date_commande')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="date_livraison_prevue" class="form-label">Livraison prévue *</label>
                                <input type="date" class="form-control @error('date_livraison_prevue') is-invalid @enderror" 
                                       id="date_livraison_prevue" name="date_livraison_prevue" 
                                       value="{{ old('date_livraison_prevue', date('Y-m-d', strtotime('+7 days'))) }}" required>
                                @error('date_livraison_prevue')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- Produits -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Produits commandés</h5>
                                <button type="button" class="btn btn-sm btn-primary" onclick="ajouterProduit()">
                                    <i class="bi bi-plus-circle"></i> Ajouter un produit
                                </button>
                            </div>
                            
                            <div id="produits-container">
                                <!-- Les produits seront ajoutés dynamiquement ici -->
                                <div id="no-produits" class="text-center py-5 text-muted">
                                    <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                                    <p class="mt-2">Aucun produit ajouté à la commande</p>
                                </div>
                            </div>
                            
                            <!-- Template de produit (caché) -->
                            <div id="produit-template" class="produit-item" style="display: none;">
                                <div class="produit-header">
                                    <h6 class="mb-0 produit-designation">Produit</h6>
                                    <button type="button" class="btn btn-sm btn-danger remove-produit" onclick="supprimerProduit(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="produit-content">
                                    <div class="form-group-compact">
                                        <label class="form-label">Produit *</label>
                                        <select class="form-select produit-select" name="produits[0][id]" required disabled
                                                onchange="mettreAJourProduit(this)">
                                            <option value="">Sélectionnez un produit</option>
                                            @foreach($produits as $produit)
                                                <option value="{{ $produit->id }}" 
                                                        data-designation="{{ $produit->designation }}"
                                                        data-prix="{{ $produit->prix_unitaire }}"
                                                        data-stock="{{ $produit->quantite_stock }}"
                                                        data-categorie="{{ $produit->sousCategorie->categorie->nom }}"
                                                        data-fournisseur-id="{{ $produit->fournisseur_id }}">
                                                    {{ $produit->code }} - {{ $produit->designation }}
                                                    (Stock: {{ $produit->quantite_stock }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group-compact">
                                        <label class="form-label">Quantité *</label>
                                        <input type="number" step="0.01" class="form-control produit-quantite" 
                                               name="produits[0][quantite]" min="0.01" required 
                                               disabled
                                               oninput="calculerSousTotal(this)">
                                    </div>
                                    <div class="form-group-compact">
                                        <label class="form-label">Prix d'achat (FCFA) *</label>
                                        <input type="number" step="0.01" class="form-control produit-prix" 
                                               name="produits[0][prix_achat]" min="0" required 
                                               disabled
                                               oninput="calculerSousTotal(this)">
                                    </div>
                                    <div class="form-group-compact">
                                        <label class="form-label">Sous-total (FCFA)</label>
                                        <input type="text" class="form-control produit-sous-total" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Récapitulatif -->
                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Récapitulatif de la commande</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Nombre de produits:</td>
                                            <td class="text-end"><span id="total-produits">0</span></td>
                                        </tr>
                                        <tr>
                                            <td>Quantité totale:</td>
                                            <td class="text-end"><span id="total-quantite">0</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Montant total:</strong></td>
                                            <td class="text-end">
                                                <h5 class="text-primary mb-0"><span id="montant-total">0</span> FCFA</h5>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <input type="hidden" name="montant_total" id="input-montant-total">
                                    
                                    <div class="d-grid mt-3">
                                        <button type="submit" class="btn btn-primary btn-lg" id="submit-btn" disabled>
                                            <i class="bi bi-check-circle"></i> Créer la commande
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let produitIndex = 0;
const produitsAjoutes = new Set();

$(document).ready(function() {
    // Ajouter un premier produit par défaut
    ajouterProduit();
    filtrerProduitsParFournisseur();
});

function ajouterProduit() {
    produitIndex++;
    
    const template = $('#produit-template').clone();
    template.attr('id', 'produit-' + produitIndex);
    template.css('display', 'block');
    template.find('select, input').prop('disabled', false);
    
    // Mettre à jour les noms des champs
    template.find('select, input').each(function() {
        const name = $(this).attr('name');
        if (name) {
            $(this).attr('name', name.replace('[0]', '[' + produitIndex + ']'));
        }
    });
    
    // Ajouter au conteneur
    $('#no-produits').hide();
    $('#produits-container').append(template);
    
    // Mettre à jour le récapitulatif
    mettreAJourRecap();
    filtrerProduitsParFournisseur();
}

function supprimerProduit(button) {
    $(button).closest('.produit-item').remove();
    
    if ($('.produit-item:visible').length === 0) {
        $('#no-produits').show();
    }
    
    mettreAJourRecap();
}

function mettreAJourProduit(select) {
    const produitItem = $(select).closest('.produit-item');
    const option = $(select).find('option:selected');
    
    if (option.val()) {
        const designation = option.data('designation');
        const prix = option.data('prix');
        
        produitItem.find('.produit-designation').text(designation);
        produitItem.find('.produit-prix').val(prix);
        
        // Calculer le sous-total
        calculerSousTotal(produitItem.find('.produit-quantite'));
    } else {
        produitItem.find('.produit-designation').text('Produit');
        produitItem.find('.produit-prix').val('');
        produitItem.find('.produit-sous-total').val('');
    }
}

function filtrerProduitsParFournisseur() {
    const fournisseurId = $('#fournisseur_id').val();

    $('.produit-item:visible .produit-select').each(function() {
        const select = $(this);
        const selectedOption = select.find('option:selected');

        select.find('option').each(function() {
            const option = $(this);
            const optionFournisseur = option.data('fournisseur-id');

            if (!option.val()) {
                option.prop('disabled', false).show();
                return;
            }

            const isVisible = fournisseurId && optionFournisseur == fournisseurId;
            option.prop('disabled', !isVisible).toggle(isVisible);
        });

        if (selectedOption.val() && selectedOption.data('fournisseur-id') != fournisseurId) {
            select.val('');
            mettreAJourProduit(select);
        }
    });
}

function calculerSousTotal(input) {
    const produitItem = $(input).closest('.produit-item');
    const quantite = parseFloat(produitItem.find('.produit-quantite').val()) || 0;
    const prix = parseFloat(produitItem.find('.produit-prix').val()) || 0;
    const sousTotal = quantite * prix;
    
    produitItem.find('.produit-sous-total').val(
        new Intl.NumberFormat('fr-FR').format(sousTotal) + ' FCFA'
    );
    
    mettreAJourRecap();
}

function mettreAJourRecap() {
    let totalProduits = 0;
    let totalQuantite = 0;
    let montantTotal = 0;
    
    $('.produit-item:visible').each(function() {
        totalProduits++;
        
        const quantite = parseFloat($(this).find('.produit-quantite').val()) || 0;
        const prix = parseFloat($(this).find('.produit-prix').val()) || 0;
        
        totalQuantite += quantite;
        montantTotal += quantite * prix;
    });
    
    $('#total-produits').text(totalProduits);
    $('#total-quantite').text(totalQuantite.toFixed(2));
    $('#montant-total').text(new Intl.NumberFormat('fr-FR').format(montantTotal));
    $('#input-montant-total').val(montantTotal);
    
    // Activer/désactiver le bouton de soumission
    $('#submit-btn').prop('disabled', montantTotal <= 0 || totalProduits === 0);
}

// Validation du formulaire
$('#commandeForm').submit(function(e) {
    const montantTotal = parseFloat($('#input-montant-total').val()) || 0;
    
    if (montantTotal <= 0) {
        e.preventDefault();
        toastr.error('Le montant total doit être supérieur à 0');
        return false;
    }
    
    // Vérifier que tous les produits ont été sélectionnés
    let produitsValides = true;
    $('.produit-item:visible .produit-select').each(function() {
        if (!$(this).val()) {
            produitsValides = false;
            $(this).addClass('is-invalid');
        }
    });
    
    if (!produitsValides) {
        e.preventDefault();
        toastr.error('Veuillez sélectionner un produit pour tous les éléments');
        return false;
    }
    
    return true;
});
</script>
@endpush
@endsection
