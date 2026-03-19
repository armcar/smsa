<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KeepAdminWarm extends Command
{
    protected $signature = 'app:keep-admin-warm';

    protected $description = 'Hit the admin portal to keep compiled views/routes cached during idle periods.';

    public function handle(): int
    {
        $url = config('app.admin_warm_url', config('app.url'));
        $url = rtrim($url, '/') . '/?warmup=1';

        try {
            Http::timeout(5)
                ->withOptions(['verify' => false])
                ->get($url);

            Log::info('Admin warmup request sent to ' . $url);
        } catch (\Throwable $e) {
            Log::warning('Admin warmup failed: ' . $e->getMessage(), ['url' => $url]);
        }

        return 0;
    }
}
