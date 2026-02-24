#!/bin/bash

echo "=== Correction des routes et authentification ==="
echo ""

# 1. Vérifier les routes existantes
echo "1. Liste des routes web:"
php artisan route:list --path=login
php artisan route:list --path=register
php artisan route:list --path=logout

echo ""
echo "2. Vérification du contrôleur AuthController..."
if [ -f "app/Http/Controllers/Web/AuthController.php" ]; then
    echo "✓ AuthController existe"
    
    # Vérifier les méthodes
    grep -n "function" app/Http/Controllers/Web/AuthController.php
else
    echo "✗ AuthController n'existe pas"
    echo "Création du contrôleur..."
    
    # Copier le contrôleur depuis le contenu ci-dessus
    cat > app/Http/Controllers/Web/AuthController.php << 'EOF'
<?php
// app/Http/Controllers/Web/AuthController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.login');
    }
    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return back()->withErrors([
                'email' => 'Utilisateur non trouvé.',
            ])->onlyInput('email');
        }
        
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Mot de passe incorrect.',
            ])->onlyInput('email');
        }
        
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->filled('remember'))) {
            $request->session()->regenerate();
            
            $token = $user->createToken('web-token')->plainTextToken;
            session(['api_token' => $token]);
            
            return redirect()->intended(route('dashboard'));
        }
        
        return back()->withErrors([
            'email' => 'Les informations d\'identification sont incorrectes.',
        ])->onlyInput('email');
    }
    
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.register');
    }
    
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:gestionnaire,responsable_achat,responsable_paiement',
            'terms' => 'required|accepted',
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);
        
        Auth::login($user);
        
        $request->session()->regenerate();
        
        $token = $user->createToken('web-token')->plainTextToken;
        session(['api_token' => $token]);
        
        return redirect()->route('dashboard')
            ->with('success', 'Compte créé avec succès! Bienvenue ' . $user->name);
    }
    
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}
EOF
fi

echo ""
echo "3. Vérification du middleware RedirectIfAuthenticated..."
if [ -f "app/Http/Middleware/RedirectIfAuthenticated.php" ]; then
    echo "✓ Middleware existe"
    
    # Vérifier le contenu
    grep -n "redirect" app/Http/Middleware/RedirectIfAuthenticated.php
else
    echo "✗ Middleware n'existe pas"
fi

echo ""
echo "4. Vérification des vues d'authentification..."
if [ -f "resources/views/auth/login.blade.php" ]; then
    echo "✓ Vue login existe"
else
    echo "✗ Vue login n'existe pas"
fi

if [ -f "resources/views/auth/register.blade.php" ]; then
    echo "✓ Vue register existe"
else
    echo "✗ Vue register n'existe pas"
fi

echo ""
echo "=== Correction terminée ==="
echo "Redémarrez le serveur et testez:"
echo "1. http://localhost:8000/login"
echo "2. http://localhost:8000/register"