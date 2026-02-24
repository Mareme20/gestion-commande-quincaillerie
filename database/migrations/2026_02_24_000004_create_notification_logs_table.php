<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 30);
            $table->string('alert_signature', 191);
            $table->string('alert_type', 80)->nullable();
            $table->string('alert_level', 30)->nullable();
            $table->text('alert_message');
            $table->string('alert_link')->nullable();
            $table->date('alert_date');
            $table->timestamp('sent_at');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'alert_signature', 'alert_date'], 'notif_dedupe_unique');
            $table->index(['channel', 'alert_date']);
            $table->index('alert_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
