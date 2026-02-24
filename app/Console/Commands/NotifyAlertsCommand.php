<?php

namespace App\Console\Commands;

use App\Services\AlertNotificationService;
use App\Services\AlertService;
use Illuminate\Console\Command;

class NotifyAlertsCommand extends Command
{
    protected $signature = 'alerts:notify {--channel=all : all|email|whatsapp}';

    protected $description = 'Envoie les alertes dashboard par email et/ou WhatsApp avec deduplication journaliere.';

    public function handle(AlertService $alertService, AlertNotificationService $notificationService): int
    {
        $channelOption = strtolower((string) $this->option('channel'));
        $channels = match ($channelOption) {
            'all' => ['email', 'whatsapp'],
            'email' => ['email'],
            'whatsapp' => ['whatsapp'],
            default => null,
        };

        if ($channels === null) {
            $this->error('Option --channel invalide. Utiliser: all, email, whatsapp.');
            return self::INVALID;
        }

        $alerts = $alertService->getAlerts();
        if ($alerts->isEmpty()) {
            $this->info('Aucune alerte a notifier.');
            return self::SUCCESS;
        }

        $this->info(sprintf('%d alerte(s) detectee(s).', $alerts->count()));

        $hasError = false;

        foreach ($channels as $channel) {
            $result = $notificationService->send($alerts, $channel);

            $pending = (int) ($result['pending'] ?? 0);
            $sent = (int) ($result['sent'] ?? 0);
            $error = $result['error'] ?? null;

            if ($error) {
                $hasError = true;
                $this->warn(sprintf('[%s] pending=%d sent=%d error=%s', $channel, $pending, $sent, $error));
                continue;
            }

            $this->info(sprintf('[%s] pending=%d sent=%d', $channel, $pending, $sent));
        }

        return $hasError ? self::FAILURE : self::SUCCESS;
    }
}
