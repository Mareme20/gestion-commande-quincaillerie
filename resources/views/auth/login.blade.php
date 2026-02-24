<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Quincaillerie Barro & Frère</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(45deg, #2c3e50, #3498db);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .login-form {
            padding: 40px;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-login {
            background: linear-gradient(45deg, #3498db, #2c3e50);
            border: none;
            padding: 12px 30px;
            font-weight: 500;
            transition: transform 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            background: linear-gradient(45deg, #2980b9, #2c3e50);
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
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <h1 class="logo-text">BARRO & FRÈRE</h1>
                        <p class="logo-subtext mb-0">Gestion des Commandes</p>
                    </div>
                    
                    <div class="login-form">
                        <h3 class="text-center mb-4">Connexion</h3>
                        
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="{{ old('email') }}" required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-login btn-lg text-white">
                                    <i class="bi bi-box-arrow-in-right"></i> Se connecter
                                </button>
                            </div>
                        </form>
                        <div class="text-center mt-4">
    <p class="text-muted">
        Pas encore de compte? 
        <a href="{{ route('register') }}" class="text-primary">Créer un compte</a>
    </p>
</div>
                        <div class="text-center mt-4">
                            <p class="text-muted mb-2">Comptes de démonstration:</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-primary">Gestionnaire</small><br>
                                    <small>gestionnaire@quincaillerie.com</small>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-success">Achat</small><br>
                                    <small>achat@quincaillerie.com</small>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-warning">Paiement</small><br>
                                    <small>paiement@quincaillerie.com</small>
                                </div>
                            </div>
                            <small class="text-muted">Mot de passe: password123</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>