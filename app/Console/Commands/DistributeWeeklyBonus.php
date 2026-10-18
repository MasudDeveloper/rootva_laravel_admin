<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BonusDistributionService;
use Carbon\Carbon;

class DistributeWeeklyBonus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:distribute-weekly {--date= : Specific date within target week (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically distribute tiered weekly referral bonuses in Asia/Dhaka timezone';

    /**
     * Execute the console command.
     */
    public function handle(BonusDistributionService $service)
    {
        $dateInput = $this->option('date');
        $targetDate = $dateInput ? Carbon::parse($dateInput, 'Asia/Dhaka') : Carbon::yesterday('Asia/Dhaka');

        $this->info("Distributing weekly bonuses for week of date: " . $targetDate->format('Y-m-d') . " [Asia/Dhaka]...");

        $result = $service->distributeWeeklyBonus($targetDate);

        if ($result['success']) {
            $this->info($result['message']);
        } else {
            $this->warn($result['message']);
        }

        return Command::SUCCESS;
    }
}
