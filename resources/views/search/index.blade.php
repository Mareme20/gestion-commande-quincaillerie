@extends('layouts.app')

@section('title', 'Recherche Globale')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <form method="GET" action="{{ route('search.index') }}" class="card card-body">
            <div class="input-group">
                <input type="text" class="form-control" name="q" value="{{ $q }}" placeholder="Commande, produit, fournisseur...">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Rechercher</button>
            </div>
        </form>
    </div>
</div>

@if($q !== '')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card"><div class="card-header"><strong>Commandes</strong></div><div class="card-body">
            @forelse($resultats['commandes'] as $commande)
                <div><a href="{{ route('commandes.show', $commande) }}">CMD-{{ str_pad($commande->id, 6, '0', STR_PAD_LEFT) }}</a> <span class="text-muted">({{ $commande->etatLabel() }})</span></div>
            @empty
                <span class="text-muted">Aucun résultat</span>
            @endforelse
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-header"><strong>Produits</strong></div><div class="card-body">
            @forelse($resultats['produits'] as $produit)
                <div><a href="{{ route('produits.show', $produit) }}">{{ $produit->code }} - {{ $produit->designation }}</a></div>
            @empty
                <span class="text-muted">Aucun résultat</span>
            @endforelse
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-header"><strong>Fournisseurs</strong></div><div class="card-body">
            @forelse($resultats['fournisseurs'] as $fournisseur)
                <div><a href="{{ route('fournisseurs.show', $fournisseur) }}">{{ $fournisseur->nom }}</a> <span class="text-muted">({{ $fournisseur->numero }})</span></div>
            @empty
                <span class="text-muted">Aucun résultat</span>
            @endforelse
        </div></div>
    </div>
</div>
@endif
@endsection
