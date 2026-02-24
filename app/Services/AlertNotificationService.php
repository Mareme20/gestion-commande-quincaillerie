<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertNotificationService
{
    public function send(Collection $alerts, string $channel): array
    {
        $today = Carbon::today();

        $pending = $alerts->filter(function (array $alert) use ($channel, $today) {
            $signature = (string) ($alert['signature'] ?? '');
            if ($signature === '') {
                return false;
            }

            return !NotificationLog::where('channel', $channel)
                ->where('alert_signature', $signature)
                ->whereDate('alert_date', $today)
                ->exists();
        })->values();

        if ($pending->isEmpty()) {
            return [
                'channel' => $channel,
                'pending' => 0,
                'sent' => 0,
                'error' => null,
            ];
        }

        return match ($channel) {
            'email' => $this->sendEmail($pending, $today),
            'whatsapp' => $this->sendWhatsapp($pending, $today),
            default => [
                'channel' => $channel,
                'pending' => $pending->count(),
                'sent' => 0,
                'error' => 'Canal inconnu',
            ],
        };
    }

    private function sendEmail(Collection $alerts, Carbon $today): array
    {
        $recipients = collect(config('alerts.email_recipients', []))
            ->filter()
            ->values();

        if ($recipients->isEmpty()) {
            $recipients = User::query()
                ->whereIn('role', ['gestionnaire', 'responsable_achat', 'responsable_paiement'])
                ->whereNotNull('email')
                ->pluck('email')
                ->filter()
                ->unique()
                ->values();
        }

        if ($recipients->isEmpty()) {
            return [
                'channel' => 'email',
                'pending' => $alerts->count(),
                'sent' => 0,
                'error' => 'Aucun destinataire email configure',
            ];
        }

        $subject = sprintf('[Alertes Quincaillerie] %d alerte(s) - %s', $alerts->count(), $today->format('d/m/Y'));
        $body = $this->buildTextSummary($alerts, 'email');

        try {
            Mail::raw($body, function ($message) use ($recipients, $subject) {
                $message->to($recipients->all())
                    ->subject($subject);
            });
        } catch (\Throwable $e) {
            return [
                'channel' => 'email',
                'pending' => $alerts->count(),
                'sent' => 0,
                'error' => $e->getMessage(),
            ];
        }

        $this->persistLogs($alerts, 'email', $today, ['recipients' => $recipients->all()]);

        return [
            'channel' => 'email',
            'pending' => $alerts->count(),
            'sent' => $alerts->count(),
            'error' => null,
        ];
    }

    private function sendWhatsapp(Collection $alerts, Carbon $today): array
    {
        $webhookUrl = (string) config('alerts.whatsapp.webhook_url', '');
        if ($webhookUrl === '') {
            Log::warning('Webhook WhatsApp non configure. Notifications ignorees.', [
                'alerts_count' => $alerts->count(),
            ]);

            return [
                'channel' => 'whatsapp',
                'pending' => $alerts->count(),
                'sent' => 0,
                'error' => 'WHATSAPP_WEBHOOK_URL non configure',
            ];
        }

        $payload = [
            'to' => config('alerts.whatsapp.to'),
            'generated_at' => now()->toIso8601String(),
            'count' => $alerts->count(),
            'message' => $this->buildTextSummary($alerts, 'whatsapp'),
            'alerts' => $alerts->map(fn (array $alert) => [
                'type' => $alert['type'] ?? null,
                'niveau' => $alert['niveau'] ?? null,
                'message' => $alert['message'] ?? null,
                'lien' => $alert['lien'] ?? null,
            ])->values()->all(),
        ];

        $request = Http::timeout(15)->acceptJson();
        $token = (string) config('alerts.whatsapp.token', '');
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        try {
            $response = $request->post($webhookUrl, $payload);
        } catch (\Throwable $e) {
            return [
                'channel' => 'whatsapp',
                'pending' => $alerts->count(),
                'sent' => 0,
                'error' => $e->getMessage(),
            ];
        }

        if (!$response->successful()) {
            return [
                'channel' => 'whatsapp',
                'pending' => $alerts->count(),
                'sent' => 0,
                'error' => 'Webhook HTTP ' . $response->status(),
            ];
        }

        $this->persistLogs($alerts, 'whatsapp', $today, [
            'webhook' => $webhookUrl,
            'to' => config('alerts.whatsapp.to'),
        ]);

        return [
            'channel' => 'whatsapp',
            'pending' => $alerts->count(),
            'sent' => $alerts->count(),
            'error' => null,
        ];
    }

    private function buildTextSummary(Collection $alerts, string $channel): string
    {
        $prefix = $channel === 'whatsapp' ? "Alertes quincaillerie" : "Alerte systeme quincaillerie";

        $lines = [
            $prefix . ' - ' . now()->format('d/m/Y H:i'),
            'Nombre total: ' . $alerts->count(),
            '',
        ];

        foreach ($alerts as $index => $alert) {
            $lines[] = sprintf(
                '%d. [%s] %s',
                $index + 1,
                strtoupper((string) ($alert['niveau'] ?? 'info')),
                (string) ($alert['message'] ?? '')
            );

            if (!empty($alert['lien'])) {
                $lines[] = '   ' . $alert['lien'];
            }
        }

        return implode("\n", $lines);
    }

    private function persistLogs(Collection $alerts, string $channel, Carbon $today, array $context = []): void
    {
        $now = now();

        foreach ($alerts as $alert) {
            NotificationLog::updateOrCreate(
                [
                    'channel' => $channel,
                    'alert_signature' => (string) ($alert['signature'] ?? sha1((string) ($alert['message'] ?? ''))),
                    'alert_date' => $today->toDateString(),
                ],
                [
                    'alert_type' => $alert['type'] ?? null,
                    'alert_level' => $alert['niveau'] ?? null,
                    'alert_message' => $alert['message'] ?? '',
                    'alert_link' => $alert['lien'] ?? null,
                    'sent_at' => $now,
                    'context' => $context,
                ]
            );
        }
    }
}
