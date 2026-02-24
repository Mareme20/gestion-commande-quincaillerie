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
    /**
     * Afficher le formulaire de connexion
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.login');
    }
    
    /**
     * Traiter la tentative de connexion
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        // Vérifier si l'utilisateur existe
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return back()->withErrors([
                'email' => 'Utilisateur non trouvé.',
            ])->onlyInput('email');
        }
        
        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Mot de passe incorrect.',
            ])->onlyInput('email');
        }
        
        // Authentifier l'utilisateur
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->filled('remember'))) {
            $request->session()->regenerate();
            
            // Créer un token API pour les appels AJAX
            $token = $user->createToken('web-token')->plainTextToken;
            session(['api_token' => $token]);
            
            return redirect()->intended(route('dashboard'));
        }
        
        return back()->withErrors([
            'email' => 'Les informations d\'identification sont incorrectes.',
        ])->onlyInput('email');
    }
    
    /**
     * Afficher le formulaire d'inscription
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.register');
    }
    
    /**
     * Traiter l'inscription d'un nouvel utilisateur
     */
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
        
        // Créer un token API
        $token = $user->createToken('web-token')->plainTextToken;
        session(['api_token' => $token]);
        
        return redirect()->route('dashboard')
            ->with('success', 'Compte créé avec succès! Bienvenue ' . $user->name);
    }
    
    /**
     * Déconnecter l'utilisateur
     */
    public function logout(Request $request)
{
    // Déconnecter d'abord la session web
    Auth::logout();
    
    // Invalider la session
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    // Nettoyer le token API de la session
    if ($request->session()->has('api_token')) {
        $request->session()->forget('api_token');
    }
    
    // Essayer de révoquer le token Sanctum si possible
    try {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
    } catch (\Exception $e) {
        // Ignorer les erreurs de token
    }
    
    return redirect()->route('login');
}
}