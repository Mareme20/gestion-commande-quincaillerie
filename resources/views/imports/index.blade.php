@extends('layouts.app')

@section('title', 'Imports CSV')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Imports CSV</h1>
        <p class="text-muted">Import rapide des fournisseurs et produits.</p>
    </div>
</div>

@if(session('import_errors'))
<div class="alert alert-warning">
    <strong>Détails erreurs import:</strong>
    <ul class="mb-0 mt-2">
        @foreach(session('import_errors') as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Import Fournisseurs</strong></div>
            <div class="card-body">
                <p class="small text-muted">Colonnes attendues: <code>numero;nom;adresse</code></p>
                <form action="{{ route('imports.fournisseurs') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-upload"></i> Importer fournisseurs</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Import Produits</strong></div>
            <div class="card-body">
                <p class="small text-muted">Colonnes attendues: <code>code;designation;quantite_stock;prix_unitaire;sous_categorie;fournisseur_numero</code></p>
                <form action="{{ route('imports.produits') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-upload"></i> Importer produits</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
