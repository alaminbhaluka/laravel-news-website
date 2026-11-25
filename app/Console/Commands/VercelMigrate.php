<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VercelMigrate extends Command
{
    protected $signature = 'vercel:migrate';

    protected $description = 'Run database migrations under a PostgreSQL advisory lock';

    public function handle()
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            return $this->call('migrate', ['--force' => true]);
        }

        $lockId = 7319462026;
        $connection->select('SELECT pg_advisory_lock(?)', [$lockId]);

        try {
            return $this->call('migrate', ['--force' => true]);
        } finally {
            $connection->select('SELECT pg_advisory_unlock(?)', [$lockId]);
        }
    }
}
