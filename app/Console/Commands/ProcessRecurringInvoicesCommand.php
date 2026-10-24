<?php

namespace App\Console\Commands;

use App\Services\RecurringInvoiceService;
use Illuminate\Console\Command;

class ProcessRecurringInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:process-recurring {--team= : Specific tenant team ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all active recurring invoice profiles due for issuance and generate Sales Orders';

    /**
     * Execute the console command.
     */
    public function handle(RecurringInvoiceService $service): int
    {
        $teamId = $this->option('team') ? (int) $this->option('team') : null;

        $this->info("Scanning for due recurring invoices" . ($teamId ? " for Team #{$teamId}..." : "..."));

        $result = $service->processAllDue($teamId);

        $this->info("Found {$result['total_due']} due subscription profile(s).");
        $this->info("Successfully generated {$result['generated_count']} invoice(s).");

        if (!empty($result['generated'])) {
            $this->table(
                ['Subscription #', 'Sales Order #', 'Total Amount'],
                array_map(fn ($item) => [
                    $item['subscription_number'],
                    $item['order_number'],
                    number_format($item['total_amount'], 2),
                ], $result['generated'])
            );
        }

        if (!empty($result['errors'])) {
            $this->error("Encountered " . count($result['errors']) . " error(s) during processing:");
            $this->table(
                ['Subscription #', 'Error Message'],
                array_map(fn ($err) => [
                    $err['subscription_number'],
                    $err['error'],
                ], $result['errors'])
            );
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
