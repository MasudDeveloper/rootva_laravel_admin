<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pcash:check-pending', function () {
    \App\Models\PcashRechargeLog::checkAndUpdatePendingLogs();
    $this->info('Pending PCash recharges checked and updated.');
})->purpose('Check and update pending/processing PCash recharge statuses');

\Illuminate\Support\Facades\Schedule::command('pcash:check-pending')
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/cron_pcash.log'));

\Illuminate\Support\Facades\Schedule::command('pcash:reconcile-payments')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/cron_payments.log'));

\Illuminate\Support\Facades\Schedule::command('microjob:auto-approve')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/cron_microjobs.log'));
