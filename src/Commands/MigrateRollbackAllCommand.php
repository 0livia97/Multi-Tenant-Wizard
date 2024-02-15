<?php

namespace Easy2Dev\MultiTenantWizard\Commands;

use Easy2Dev\MultiTenantWizard\Helpers\DBArtisanHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MigrateRollbackAllCommand extends Command
{
    protected $signature = 'tenant:migrate:rollback {prefix} {--step=}';
    protected $description = 'Rollback migrations for all databases with a specific prefix';

    public function handle()
    {
        $prefix = $this->argument('prefix');
        $step = $this->option('step') ?? 3;

        $databases = $this->getDatabasesWithPrefix($prefix);

        if (empty($databases)) {
            $this->info('No databases found with the specified prefix.');
            return;
        }

        foreach ($databases as $database) {
            try {
                DBArtisanHelper::configure($database);
                $this->rollbackMigrations($database, $step);
            } catch (\Throwable $exception) {
                $this->error("An error occurred while rolling back migrations for database '$database': " . $exception->getMessage());
            }
        }
    }

    protected function getDatabasesWithPrefix(string $prefix): array
    {
        $query = "SELECT datname FROM pg_database WHERE datistemplate = false AND datname LIKE '{$prefix}%'";
        $databaseNames = DB::connection('pgsql')->select($query);

        return array_column($databaseNames, 'datname');
    }

    protected function rollbackMigrations(string $userDatabase, int $step): void
    {
        $this->call('migrate:rollback', [
            '--database' => $userDatabase,
            '--step' => $step,
        ]);

        $this->info("Rolling back migrations for database '$userDatabase'.");
        $this->line(Artisan::output());
    }
}