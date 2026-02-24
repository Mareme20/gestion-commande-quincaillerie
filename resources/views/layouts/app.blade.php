<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Quincaillerie Barro & Frère</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #1a1a2e 100%);
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(52, 152, 219, 0.2);
            color: white;
            border-left-color: #3498db;
        }
        .sidebar .nav-link i {
            width: 25px;
        }
        .sidebar .nav .nav-link {
            padding-left: 50px !important;
            font-size: 0.9rem;
            border-left: none;
        }
        .sidebar .nav .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            border-left: 3px solid #2ecc71;
        }
        .navbar-brand {
            font-weight: bold;
            color: #2c3e50 !important;
        }
        .card-stat {
            border-radius: 10px;
            transition: transform 0.3s;
        }
        .card-stat:hover {
            transform: translateY(-5px);
        }
        .bg-primary-gradient {
            background: linear-gradient(45deg, #3498db, #2c3e50);
        }
        .bg-success-gradient {
            background: linear-gradient(45deg, #27ae60, #16a085);
        }
        .bg-warning-gradient {
            background: linear-gradient(45deg, #f39c12, #d35400);
        }
        .bg-danger-gradient {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .table th {
            background-color: #f8f9fa;
            color: #2c3e50;
        }
        .badge-etat {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
        }
        .badge-en_cours {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-livre {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .badge-paye {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-annule {
            background-color: #f8d7da;
            color: #721c24;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
        /* Animation pour les chevrons */
        .bi-chevron-down {
            transition: transform 0.3s ease;
            font-size: 0.8rem;
        }
        [aria-expanded="true"] .bi-chevron-down {
            transform: rotate(180deg);
        }
        /* Style pour le menu actif */
        .nav-link[data-bs-toggle="collapse"] {
            position: relative;
        }
        /* Badge pour les compteurs */
        .sidebar-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
        }
        .alertes-menu {
            min-width: 360px;
            max-height: 420px;
            overflow-y: auto;
        }
        /* Logo et branding */
        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-brand h4 {
            color: white;
            margin-bottom: 0;
            font-weight: bold;
        }
        .sidebar-brand .text-warning {
            font-size: 0.9rem;
        }
        /* User info */
        .user-info {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 20px;
            margin-top: auto;
        }
        /* Scrollbar personnalisée */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(52, 152, 219, 0.5);
            border-radius: 10px;
        }
        /* Animation des sous-menus */
        .collapse {
            transition: all 0.3s ease;
        }
        /* Hover effect pour les sous-menus */
        .nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            padding-left: 55px !important;
            transition: all 0.3s ease;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="sidebar-brand">
                    <h4 class="mb-1">BARRO & FRÈRE</h4>
                    <small class="text-warning">Gestion des Commandes</small>
                </div>
                
                <div class="position-sticky pt-3" style="height: calc(100vh - 100px); overflow-y: auto;">
                    <ul class="nav flex-column">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Tableau de bord
                            </a>
                        </li>
                        
                        @auth
                            <!-- Menu Catégories (Gestionnaire uniquement) -->
                            @if(Auth::user()->isGestionnaire())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('categories.*') || request()->routeIs('sous-categories.*') ? 'active' : '' }}" 
                                   data-bs-toggle="collapse" href="#categoriesCollapse" role="button" 
                                   aria-expanded="{{ request()->routeIs('categories.*') || request()->routeIs('sous-categories.*') ? 'true' : 'false' }}" 
                                   aria-controls="categoriesCollapse">
                                    <i class="bi bi-tags"></i> Catégories
                                    <i class="bi bi-chevron-down float-end mt-1"></i>
                                </a>
                                <div class="collapse {{ request()->routeIs('categories.*') || request()->routeIs('sous-categories.*') ? 'show' : '' }}" id="categoriesCollapse">
                                    <ul class="nav flex-column ps-3">
                                        <li class="nav-item">
                                            <a class="nav-link {{ request()->routeIs('categories.index') ? 'active' : '' }}" 
                                               href="{{ route('categories.index') }}">
                                                <i class="bi bi-list-ul"></i> Liste des catégories
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ request()->routeIs('sous-categories.*') ? 'active' : '' }}" 
                                               href="{{ route('sous-categories.index') }}">
                                                <i class="bi bi-diagram-3"></i> Sous-catégories
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{ request()->routeIs('categories.create') ? 'active' : '' }}" 
                                               href="{{ route('categories.create') }}">
                                                <i class="bi bi-plus-circle"></i> Nouvelle catégorie
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            
                            <!-- Produits -->
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('produits.*') ? 'active' : '' }}" href="{{ route('produits.index') }}">
                                    <i class="bi bi-box"></i> Produits
                                </a>
                            </li>
                            
                            <!-- Fournisseurs -->
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('fournisseurs.*') ? 'active' : '' }}" href="{{ route('fournisseurs.index') }}">
                                    <i class="bi bi-truck"></i> Fournisseurs
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.index') }}">
                                    <i class="bi bi-upload"></i> Imports CSV
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}">
                                    <i class="bi bi-journal-text"></i> Journal d'audit
                                </a>
                            </li>
                            @endif
                            
                            <!-- Menu Opérations -->
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('commandes.*') || request()->routeIs('versements.*') ? 'active' : '' }}" 
                                   data-bs-toggle="collapse" href="#operationsCollapse" role="button"
                                   aria-expanded="{{ request()->routeIs('commandes.*') || request()->routeIs('versements.*') ? 'true' : 'false' }}" 
                                   aria-controls="operationsCollapse">
                                    <i class="bi bi-clipboard-data"></i> Opérations
                                    <i class="bi bi-chevron-down float-end mt-1"></i>
                                </a>
                                <div class="collapse {{ request()->routeIs('commandes.*') || request()->routeIs('versements.*') ? 'show' : '' }}" id="operationsCollapse">
                                    <ul class="nav flex-column ps-3">
                                        @if(Auth::user()->isResponsableAchat()  )
                                        <li class="nav-item">
                                            <a class="nav-link {{ request()->routeIs('commandes.*') ? 'active' : '' }}" 
                                               href="{{ route('commandes.index') }}">
                                                <i class="bi bi-cart-check"></i> Commandes
                                            </a>
                                        </li>
                                        @endif
                                        
                                        @if(Auth::user()->isResponsablePaiement()  )
                                        <li class="nav-item">
                                            <a class="nav-link {{ request()->routeIs('versements.*') ? 'active' : '' }}" 
                                               href="{{ route('versements.index') }}">
                                                <i class="bi bi-cash-coin"></i> Versements
                                            </a>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                            
                            <!-- Information utilisateur -->
                            <div class="user-info">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-person-circle fs-4 text-light"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0 text-light">{{ Auth::user()->name }}</h6>
                                        <small class="text-warning">
                                            @php
                                                $roleLabels = [
                                                    'gestionnaire' => 'Gestionnaire',
                                                    'responsable_achat' => 'Responsable Achat',
                                                    'responsable_paiement' => 'Responsable Paiement'
                                                ];
                                            @endphp
                                            {{ $roleLabels[Auth::user()->role] ?? Auth::user()->role }}
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Déconnexion -->
                                <div class="mt-3">
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endauth
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Top navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4 shadow-sm">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="d-flex justify-content-between w-100 align-items-center">
                            <div>
                                <h4 class="mb-0 text-primary">@yield('title')</h4>
                                @hasSection('subtitle')
                                    <p class="text-muted mb-0">@yield('subtitle')</p>
                                @endif
                            </div>
                            <div class="d-flex align-items-center">
                                @auth
                                <form method="GET" action="{{ route('search.index') }}" class="me-3 d-none d-md-block">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Recherche globale...">
                                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                                    </div>
                                </form>
                                @endauth
                                @auth
                                <div class="dropdown me-3">
                                    <button class="btn btn-outline-secondary position-relative" type="button" id="alertesDropdown"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-bell"></i>
                                        <span id="alertes-count"
                                              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end alertes-menu" aria-labelledby="alertesDropdown" id="alertes-list">
                                        <li class="dropdown-item text-muted">Aucune alerte</li>
                                    </ul>
                                </div>
                                @endauth
                                <span class="text-muted me-3">
                                    <i class="bi bi-calendar"></i> {{ date('d/m/Y') }}
                                </span>
                                @hasSection('header-actions')
                                    <div class="header-actions">
                                        @yield('header-actions')
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Messages de session -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i> {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle me-2"></i> {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Content -->
                <div class="container-fluid">
                    @yield('content')
                </div>

                <!-- Footer -->
                <footer class="mt-5 pt-4 border-top">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-muted">
                                &copy; {{ date('Y') }} Quincaillerie Barro & Frère. Tous droits réservés.
                            </p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="text-muted">
                                <i class="bi bi-shield-check text-success"></i>
                                Système sécurisé - v1.0.0
                            </p>
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <script>
        // Initialiser DataTables
        $(document).ready(function() {
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
                },
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']]
            });
        });

        // Toastr configuration
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000",
            "extendedTimeOut": "2000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        // Afficher les messages de session avec Toastr
        @if(session('success'))
            toastr.success("{{ session('success') }}");
        @endif

        @if(session('error'))
            toastr.error("{{ session('error') }}");
        @endif

        @if(session('warning'))
            toastr.warning("{{ session('warning') }}");
        @endif

        @if(session('info'))
            toastr.info("{{ session('info') }}");
        @endif

        // Gérer les menus déroulants
        $(document).ready(function() {
            // Sauvegarder l'état des menus dans localStorage
            $('.collapse').on('show.bs.collapse', function() {
                localStorage.setItem('collapse_' + this.id, 'show');
            });
            
            $('.collapse').on('hide.bs.collapse', function() {
                localStorage.setItem('collapse_' + this.id, 'hide');
            });
            
            // Restaurer l'état des menus
            $('.collapse').each(function() {
                const state = localStorage.getItem('collapse_' + this.id);
                if (state === 'show') {
                    $(this).collapse('show');
                }
            });
            
            // Mettre à jour les chevrons
            $('.collapse').on('show.bs.collapse', function() {
                $(this).prev().find('.bi-chevron-down').css('transform', 'rotate(180deg)');
            });
            
            $('.collapse').on('hide.bs.collapse', function() {
                $(this).prev().find('.bi-chevron-down').css('transform', 'rotate(0deg)');
            });
            
            // Gérer le responsive de la sidebar
            $('.navbar-toggler').click(function() {
                $('.sidebar').toggleClass('d-none');
            });
            
            // Fermer la sidebar sur mobile quand on clique ailleurs
            $(document).click(function(event) {
                if ($(window).width() < 768) {
                    if (!$(event.target).closest('.sidebar').length && 
                        !$(event.target).closest('.navbar-toggler').length) {
                        $('.sidebar').addClass('d-none');
                    }
                }
            });
            
            // Charger les compteurs pour la sidebar (si API disponible)
            function loadSidebarCounters() {
                const apiToken = localStorage.getItem('api_token') || '{{ session("api_token") }}';
                
                if (apiToken) {
                    $.ajax({
                        url: '/api/dashboard/counters',
                        type: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken,
                            'Accept': 'application/json'
                        },
                        success: function(response) {
                            // Mettre à jour les compteurs si les éléments existent
                            if (response.categories_count !== undefined && $('#categories-count').length) {
                                $('#categories-count').text(response.categories_count).removeClass('d-none');
                            }
                            if (response.sous_categories_count !== undefined && $('#sous-categories-count').length) {
                                $('#sous-categories-count').text(response.sous_categories_count).removeClass('d-none');
                            }
                            if (response.produits_count !== undefined && $('#produits-count').length) {
                                $('#produits-count').text(response.produits_count).removeClass('d-none');
                            }
                            if (response.commandes_en_cours_count !== undefined && $('#commandes-en-cours-count').length) {
                                $('#commandes-en-cours-count').text(response.commandes_en_cours_count).removeClass('d-none');
                            }
                        },
                        error: function(xhr) {
                            console.log('Impossible de charger les compteurs');
                        }
                    });
                }
            }
            
            // Charger les compteurs après 1 seconde
            setTimeout(loadSidebarCounters, 1000);

            function loadAlertes() {
                if (!$('#alertes-list').length) {
                    return;
                }
                $.ajax({
                    url: '/api/dashboard/alertes',
                    type: 'GET',
                    success: function(response) {
                        const countEl = $('#alertes-count');
                        const listEl = $('#alertes-list');

                        if (!response || !Array.isArray(response.alertes) || response.alertes.length === 0) {
                            countEl.addClass('d-none').text('0');
                            listEl.html('<li class="dropdown-item text-muted">Aucune alerte</li>');
                            return;
                        }

                        countEl.removeClass('d-none').text(response.total || response.alertes.length);
                        let html = '';
                        response.alertes.forEach(function(alerte) {
                            const badgeClass = alerte.niveau === 'danger' ? 'bg-danger' : 'bg-warning text-dark';
                            html += `
                                <li>
                                    <a class="dropdown-item" href="${alerte.lien || '#'}">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="badge ${badgeClass}">${alerte.niveau || 'info'}</span>
                                            <span>${alerte.message}</span>
                                        </div>
                                    </a>
                                </li>
                            `;
                        });
                        listEl.html(html);
                    },
                    error: function() {
                        $('#alertes-list').html('<li class="dropdown-item text-muted">Impossible de charger les alertes</li>');
                    }
                });
            }

            loadAlertes();
            setInterval(loadAlertes, 120000);
        });

        // Formater les nombres
        function formatNumber(number) {
            return new Intl.NumberFormat('fr-FR').format(number);
        }

        // Formater l'argent
        function formatMoney(amount) {
            return formatNumber(amount) + ' FCFA';
        }

        // Confirmation avant suppression
        $(document).on('submit', 'form[data-confirm]', function(e) {
            const message = $(this).data('confirm') || 'Êtes-vous sûr de vouloir effectuer cette action ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });

        // Auto-dismiss alerts after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert:not(.alert-permanent)').alert('close');
            }, 5000);
        });
    </script>
    
    @stack('scripts')
</body>
</html>
