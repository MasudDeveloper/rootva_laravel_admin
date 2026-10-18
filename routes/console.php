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

// Daily Bonus Distribution (Runs every night at 00:05 AM Asia/Dhaka after day ends)
\Illuminate\Support\Facades\Schedule::command('bonus:distribute-daily')
    ->dailyAt('00:05')
    ->timezone('Asia/Dhaka')
    ->appendOutputTo(storage_path('logs/cron_daily_bonus.log'));

// Weekly Bonus Distribution (Runs every Saturday at 00:10 AM Asia/Dhaka after week ends on Friday midnight)
\Illuminate\Support\Facades\Schedule::command('bonus:distribute-weekly')
    ->saturdays()
    ->at('00:10')
    ->timezone('Asia/Dhaka')
    ->appendOutputTo(storage_path('logs/cron_weekly_bonus.log'));

