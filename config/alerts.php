<?php

$csvRecipients = (string) env('ALERT_EMAIL_RECIPIENTS', '');
$emailRecipients = array_values(array_filter(array_map('trim', explode(',', $csvRecipients))));

return [
    'stock_critique_seuil' => (int) env('ALERT_STOCK_CRITIQUE_SEUIL', 10),
    'dette_fournisseur_seuil' => (float) env('ALERT_DETTE_FOURNISSEUR_SEUIL', 1000000),

    'email_recipients' => $emailRecipients,

    'whatsapp' => [
        'webhook_url' => env('WHATSAPP_WEBHOOK_URL'),
        'token' => env('WHATSAPP_WEBHOOK_TOKEN'),
        'to' => env('WHATSAPP_TO'),
    ],
];
