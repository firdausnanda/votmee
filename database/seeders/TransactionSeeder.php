<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            WalletSeeder::class,
            PointLedgerSeeder::class,
            SupportEventSeeder::class,
            CommentSeeder::class,
            ReportSeeder::class,
            ModerationActionSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
