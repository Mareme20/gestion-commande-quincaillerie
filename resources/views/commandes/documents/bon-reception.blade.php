<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de Réception - CMD-{{ str_pad($commande->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; margin: 10px 0; }
        .muted { color: #6b7280; font-size: 12px; }
        .box { border: 1px solid #d1d5db; border-radius: 8px; padding: 12px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
        .print-actions { margin-bottom: 12px; }
        @media print { .print-actions { display: none; } }
    </style>
</head>
<body>
    <div class="print-actions">
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
        <a href="{{ route('commandes.show', $commande) }}">Retour</a>
    </div>

    <div class="header">
        <div>
            <div class="title">BON DE RÉCEPTION</div>
            <div class="muted">N° CMD-{{ str_pad($commande->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="muted">Date édition: {{ $dateEdition->format('d/m/Y H:i') }}</div>
        </div>
        <div class="right">
            <div><strong>QUINCAILLERIE BARRO & FRÈRE</strong></div>
            <div class="muted">Document interne</div>
        </div>
    </div>

    <div class="box">
        <div><strong>Fournisseur:</strong> {{ $commande->fournisseur->nom }}</div>
        <div><strong>Date commande:</strong> {{ $commande->date_commande->format('d/m/Y') }}</div>
        <div><strong>Date réception:</strong> {{ optional($commande->date_livraison_reelle)->format('d/m/Y') }}</div>
        <div><strong>État:</strong> {{ $commande->etatLabel() }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Désignation</th>
                <th>Quantité reçue</th>
                <th>Prix d'achat</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commande->produits as $produit)
                @php $st = $produit->pivot->quantite * $produit->pivot->prix_achat; @endphp
                <tr>
                    <td>{{ $produit->code }}</td>
                    <td>{{ $produit->designation }}</td>
                    <td>{{ number_format($produit->pivot->quantite, 2, ',', ' ') }}</td>
                    <td>{{ number_format($produit->pivot->prix_achat, 0, ',', ' ') }} FCFA</td>
                    <td>{{ number_format($st, 0, ',', ' ') }} FCFA</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="right"><strong>Montant total reçu: {{ number_format($commande->montant_total, 0, ',', ' ') }} FCFA</strong></p>
</body>
</html>
