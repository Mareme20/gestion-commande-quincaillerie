<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Quincaillerie Barro & Frère</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(45deg, #2c3e50, #3498db);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .register-form {
            padding: 40px;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-register {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            border: none;
            padding: 12px 30px;
            font-weight: 500;
            transition: transform 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            background: linear-gradient(45deg, #229954, #27ae60);
        }
        .logo-text {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0;
        }
        .logo-subtext {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        .role-badge {
            cursor: pointer;
            transition: all 0.3s;
        }
        .role-badge:hover {
            transform: scale(1.05);
        }
        .role-badge.active {
            background: #3498db !important;
            color: white;
            border-color: #3498db !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-card">
                    <div class="register-header">
                        <h1 class="logo-text">BARRO & FRÈRE</h1>
                        <p class="logo-subtext mb-0">Création de compte</p>
                    </div>
                    
                    <div class="register-form">
                        <h3 class="text-center mb-4">Créer un compte</h3>
                        
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Nom complet *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="{{ old('name') }}" required autofocus>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="{{ old('email') }}" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Mot de passe *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <small class="text-muted">Minimum 8 caractères</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock-fill"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password_confirmation" 
                                               name="password_confirmation" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label d-block mb-3">Rôle *</label>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <div class="role-badge border border-primary rounded p-3 text-center 
                                                    {{ old('role') == 'gestionnaire' ? 'active' : '' }}"
                                             onclick="selectRole('gestionnaire')">
                                            <i class="bi bi-gear-fill h3 d-block text-primary"></i>
                                            <strong>Gestionnaire</strong>
                                            <small class="d-block text-muted">Administration complète</small>
                                            <input type="radio" name="role" value="gestionnaire" 
                                                   id="role_gestionnaire" 
                                                   {{ old('role') == 'gestionnaire' ? 'checked' : '' }}
                                                   style="display: none;" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="role-badge border border-success rounded p-3 text-center
                                                    {{ old('role') == 'responsable_achat' ? 'active' : '' }}"
                                             onclick="selectRole('responsable_achat')">
                                            <i class="bi bi-cart-check-fill h3 d-block text-success"></i>
                                            <strong>Responsable Achat</strong>
                                            <small class="d-block text-muted">Gestion des commandes</small>
                                            <input type="radio" name="role" value="responsable_achat" 
                                                   id="role_achat" 
                                                   {{ old('role') == 'responsable_achat' ? 'checked' : '' }}
                                                   style="display: none;" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="role-badge border border-warning rounded p-3 text-center
                                                    {{ old('role') == 'responsable_paiement' ? 'active' : '' }}"
                                             onclick="selectRole('responsable_paiement')">
                                            <i class="bi bi-cash-coin h3 d-block text-warning"></i>
                                            <strong>Responsable Paiement</strong>
                                            <small class="d-block text-muted">Gestion des versements</small>
                                            <input type="radio" name="role" value="responsable_paiement" 
                                                   id="role_paiement" 
                                                   {{ old('role') == 'responsable_paiement' ? 'checked' : '' }}
                                                   style="display: none;" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    J'accepte les <a href="#" class="text-primary">conditions d'utilisation</a> et la 
                                    <a href="#" class="text-primary">politique de confidentialité</a>
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-register btn-lg text-white">
                                    <i class="bi bi-person-plus"></i> Créer le compte
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted">
                                Déjà un compte? 
                                <a href="{{ route('login') }}" class="text-primary">Connectez-vous</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectRole(role) {
            // Désactiver toutes les badges
            document.querySelectorAll('.role-badge').forEach(badge => {
                badge.classList.remove('active');
            });
            
            // Activer la badge sélectionnée
            const selectedBadge = document.querySelector(`[onclick="selectRole('${role}')"]`);
            selectedBadge.classList.add('active');
            
            // Cochez la radio correspondante
            const radio = document.getElementById(`role_${role === 'responsable_achat' ? 'achat' : 
                                                  role === 'responsable_paiement' ? 'paiement' : 
                                                  role}`);
            radio.checked = true;
        }
        
        // Initialiser la sélection si une valeur existe
        const initialRole = "{{ old('role') }}";
        if (initialRole) {
            selectRole(initialRole);
        }
    </script>
</body>
</html>