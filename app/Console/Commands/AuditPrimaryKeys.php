<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditPrimaryKeys extends Command
{
    protected $signature = 'db:audit-primary-keys {--database= : Configured connection name} {--all-schemas : Check all accessible non-system schemas on this server}';

    protected $description = 'Read-only audit of MySQL tables missing primary keys (exit 1 if any are found).';

    public function handle(): int
    {
        $connection = DB::connection($this->option('database'));
        if ($connection->getDriverName() !== 'mysql') {
            $this->error('This audit requires a MySQL connection.');
            return self::FAILURE;
        }

        $scope = $this->option('all-schemas') ? '' : ' AND t.TABLE_SCHEMA = DATABASE()';
        $rows = $connection->select(
            "SELECT t.TABLE_SCHEMA AS database_name, t.TABLE_NAME AS table_name,
                    t.TABLE_ROWS AS estimated_rows
             FROM information_schema.TABLES t
             WHERE t.TABLE_TYPE = 'BASE TABLE'
               AND t.TABLE_SCHEMA NOT IN ('mysql', 'information_schema', 'performance_schema', 'sys')
               AND NOT EXISTS (
                   SELECT 1 FROM information_schema.TABLE_CONSTRAINTS c
                   WHERE c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME
                     AND c.CONSTRAINT_TYPE = 'PRIMARY KEY'
               ){$scope}
             ORDER BY t.TABLE_SCHEMA, t.TABLE_NAME"
        );
        $this->line('Scope: '.($this->option('all-schemas') ? 'all schemas visible to this database user' : $connection->getDatabaseName()));
        if (!$rows) {
            $this->info('No tables without primary keys found in this scope.');
            return self::SUCCESS;
        }
        $this->table(['Database', 'Table', 'Estimated rows'], array_map(fn ($row) => (array) $row, $rows));
        $this->warn('Every listed table needs a primary key, including empty tables. Row counts are estimates.');
        return self::FAILURE;
    }
}
