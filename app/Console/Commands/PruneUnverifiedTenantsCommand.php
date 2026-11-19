<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Contracts\DeletesUsers;

class PruneUnverifiedTenantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:prune-unverified 
                            {--hours=24 : Prune unverified users created older than this number of hours}
                            {--all : Prune all unverified users regardless of creation time}
                            {--dry-run : List unverified tenants that would be deleted without deleting}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently remove unverified tenant owners, their teams, and associated workspace data.';

    /**
     * Execute the console command.
     */
    public function handle(DeletesUsers $userDeleter): int
    {
        $all = $this->option('all');
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $query = User::query()
            ->where('is_super_admin', false)
            ->whereNull('phone_verified_at')
            ->whereNull('email_verified_at')
            ->with(['ownedTeams']);

        if (!$all && $hours > 0) {
            $cutoff = Carbon::now()->subHours($hours);
            $query->where('created_at', '<=', $cutoff);
        }

        $unverifiedUsers = $query->get();

        if ($unverifiedUsers->isEmpty()) {
            $this->info("No unverified tenants found matching the criteria.");
            return Command::SUCCESS;
        }

        $this->warn("Found {$unverifiedUsers->count()} unverified user(s) / tenant(s):");

        $tableData = [];
        foreach ($unverifiedUsers as $user) {
            $teamNames = $user->ownedTeams->pluck('name')->join(', ') ?: 'No Owned Team';
            $tableData[] = [
                'User ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Phone' => $user->phone ?: 'N/A',
                'Teams' => $teamNames,
                'Registered At' => $user->created_at?->toDateTimeString() ?: 'N/A',
            ];
        }

        $this->table(['User ID', 'Name', 'Email', 'Phone', 'Teams', 'Registered At'], $tableData);

        if ($dryRun) {
            $this->info("[DRY RUN] No records were deleted. Remove --dry-run to permanently purge.");
            return Command::SUCCESS;
        }

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to permanently delete these unverified tenants and all their data?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $deletedCount = 0;
        foreach ($unverifiedUsers as $user) {
            try {
                $userDeleter->delete($user);
                $deletedCount++;
            } catch (\Throwable $e) {
                $this->error("Failed to delete User ID {$user->id} ({$user->name}): " . $e->getMessage());
            }
        }

        Cache::forget('superadmin_system_metrics');

        $this->info("Successfully purged {$deletedCount} unverified tenant(s) and their associated data.");

        return Command::SUCCESS;
    }
}
