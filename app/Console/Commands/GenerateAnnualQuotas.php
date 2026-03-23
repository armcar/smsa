<?php

namespace App\Console\Commands;

use App\Models\QuotaYear;
use App\Services\AnnualQuotaGenerationService;
use Illuminate\Console\Command;

class GenerateAnnualQuotas extends Command
{
    protected $signature = 'quotas:generate-annual {--year= : Ano da quota (ex: 2026)}';

    protected $description = 'Gera quotas anuais para socios elegiveis sem duplicar registos.';

    public function handle(AnnualQuotaGenerationService $service): int
    {
        $yearOption = $this->option('year');
        $quotaYear = null;

        if ($yearOption !== null) {
            $year = (int) $yearOption;
            $quotaYear = QuotaYear::query()->where('ano', $year)->first();

            if (! $quotaYear) {
                $this->error("Nao existe quota anual para o ano {$year}.");
                return self::FAILURE;
            }
        } else {
            $quotaYear = QuotaYear::query()
                ->where('ativo', true)
                ->orderByDesc('ano')
                ->first();

            if (! $quotaYear) {
                $this->error('Nao existe quota anual ativa para gerar quotas.');
                return self::FAILURE;
            }
        }

        $result = $service->generateForQuotaYear($quotaYear);

        $this->info("Ano {$quotaYear->ano}: {$result['created']} quotas criadas (elegiveis: {$result['eligible']}).");
        $this->line('Execucao idempotente: registos existentes foram preservados sem duplicacao.');

        return self::SUCCESS;
    }
}

