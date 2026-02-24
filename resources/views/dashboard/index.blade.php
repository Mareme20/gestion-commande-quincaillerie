
@extends('layouts.app')

@section('title', 'Tableau de bord')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.css">
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Tableau de bord</h1>
        <p class="text-muted">Vue d'ensemble de l'activité</p>
    </div>
</div>

<!-- Statistiques principales -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card card-stat bg-primary-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Commandes en cours</h6>
                        <h2 class="mb-0" id="commandes-en-cours">0</h2>
                    </div>
                    <i class="bi bi-cart stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card card-stat bg-warning-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Livraisons aujourd'hui</h6>
                        <h2 class="mb-0" id="livraisons-jour">0</h2>
                    </div>
                    <i class="bi bi-truck stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card card-stat bg-danger-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Dette totale</h6>
                        <h4 class="mb-0" id="dette-totale">0 FCFA</h4>
                    </div>
                    <i class="bi bi-cash-coin stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card card-stat bg-success-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Versements jour</h6>
                        <h4 class="mb-0" id="versements-jour">0 FCFA</h4>
                    </div>
                    <i class="bi bi-credit-card stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Évolution des commandes (7 derniers jours)</h6>
            </div>
            <div class="card-body">
                <div id="chart-commandes"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Répartition par état</h6>
            </div>
            <div class="card-body">
                <div id="chart-etats"></div>
            </div>
        </div>
    </div>
</div>

<!-- Tableaux rapides -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Commandes récentes</h6>
                <a href="{{ route('commandes.index') }}" class="btn btn-sm btn-primary">Voir tout</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Fournisseur</th>
                                <th>Montant</th>
                                <th>État</th>
                            </tr>
                        </thead>
                        <tbody id="commandes-recentes">
                            <!-- Les données seront chargées via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Versements récents</h6>
                <a href="{{ route('versements.index') }}" class="btn btn-sm btn-primary">Voir tout</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>N° Versement</th>
                                <th>Commande</th>
                                <th>Montant</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="versements-recents">
                            <!-- Les données seront chargées via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0"></script>
<script>
$(document).ready(function() {
    let commandesChart = null;
    let etatsChart = null;

    // Charger les statistiques
    loadDashboardStats();
    
    // Actualiser toutes les 5 minutes
    setInterval(loadDashboardStats, 300000);
    
    function loadDashboardStats() {
        $.ajax({
            url: '/api/dashboard/statistiques?periode=journee',
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            },
            success: function(response) {
                // Mettre à jour les statistiques principales
                $('#commandes-en-cours').text(response.statistiques_principales.commandes_en_cours);
                $('#livraisons-jour').text(response.statistiques_principales.commandes_livraison_journee);
                $('#dette-totale').text(formatMoney(response.statistiques_principales.dette_totale) + ' FCFA');
                $('#versements-jour').text(formatMoney(response.statistiques_principales.versements_journee) + ' FCFA');
                
                // Générer les graphiques
                generateCharts(response.evolution_commandes, response.repartition_etats || {});
                loadRecentOrders();
                loadRecentPayments();
            },
            error: function(xhr) {
                console.error('Erreur lors du chargement des statistiques');
            }
        });
    }
    
    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount);
    }
    
    function generateCharts(evolutionData, repartitionEtats) {
        // Graphique d'évolution des commandes
        const dates = evolutionData.map(item => item.jour);
        const commandesCount = evolutionData.map(item => item.commandes);
        const commandesAmount = evolutionData.map(item => item.montant);
        
        const commandesOptions = {
            series: [{
                name: "Nombre de commandes",
                type: "column",
                data: commandesCount
            }, {
                name: "Montant (K FCFA)",
                type: "line",
                data: commandesAmount.map(amount => amount / 1000)
            }],
            chart: {
                height: 350,
                type: 'line',
                toolbar: {
                    show: false
                }
            },
            stroke: {
                width: [0, 4]
            },
            dataLabels: {
                enabled: true,
                enabledOnSeries: [1]
            },
            labels: dates,
            xaxis: {
                type: 'category'
            },
            yaxis: [{
                title: {
                    text: 'Nombre de commandes',
                },
            }, {
                opposite: true,
                title: {
                    text: 'Montant (K FCFA)'
                }
            }],
            colors: ['#3498db', '#2ecc71']
        };

        if (commandesChart) {
            commandesChart.updateOptions(commandesOptions);
        } else {
            commandesChart = new ApexCharts(document.querySelector("#chart-commandes"), commandesOptions);
            commandesChart.render();
        }
        
        // Graphique de répartition par état
        const etatsSeries = [
            Number(repartitionEtats.brouillon || 0),
            Number(repartitionEtats.validee || 0),
            Number(repartitionEtats.recue || 0),
            Number(repartitionEtats.cloturee || 0),
            Number(repartitionEtats.annule || 0)
        ];

        const etatsOptions = {
            series: etatsSeries,
            chart: {
                type: 'donut',
                height: 350
            },
            labels: ['Brouillon', 'Validée', 'Reçue', 'Clôturée', 'Annulé'],
            colors: ['#f39c12', '#3498db', '#17a2b8', '#2ecc71', '#e74c3c'],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        if (etatsChart) {
            etatsChart.updateOptions(etatsOptions);
        } else {
            etatsChart = new ApexCharts(document.querySelector("#chart-etats"), etatsOptions);
            etatsChart.render();
        }
    }
    
    function loadRecentOrders() {
        $.ajax({
            url: '/api/commandes',
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            },
            success: function(response) {
                let html = '';
                response.slice(0, 5).forEach(commande => {
                    const etatClass = getEtatClass(commande.etat);
                    const etatText = getEtatText(commande.etat);
                    
                    html += `
                    <tr>
                        <td>CMD-${commande.id.toString().padStart(6, '0')}</td>
                        <td>${commande.fournisseur.nom}</td>
                        <td>${formatMoney(commande.montant_total)} FCFA</td>
                        <td><span class="badge badge-etat ${etatClass}">${etatText}</span></td>
                    </tr>
                    `;
                });
                $('#commandes-recentes').html(html);
            }
        });
    }
    
    function loadRecentPayments() {
        $.ajax({
            url: '/api/versements',
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token'),
                'Accept': 'application/json'
            },
            success: function(response) {
                let html = '';
                response.slice(0, 5).forEach(versement => {
                    html += `
                    <tr>
                        <td>${versement.numero_versement}</td>
                        <td>CMD-${versement.commande_id.toString().padStart(6, '0')}</td>
                        <td>${formatMoney(versement.montant)} FCFA</td>
                        <td>${new Date(versement.date_versement).toLocaleDateString('fr-FR')}</td>
                    </tr>
                    `;
                });
                $('#versements-recents').html(html);
            }
        });
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
    
    // Récupérer le token depuis la session
    const apiToken = '{{ Auth::user()->createToken("web-token")->plainTextToken }}';
    localStorage.setItem('api_token', apiToken);
});
</script>
@endpush
@endsection
