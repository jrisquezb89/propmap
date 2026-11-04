<?php

namespace App\Console\Commands;

use App\Models\Property;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

/**
 * Idempotent seeding for container boot. Render's free tier has no shell or
 * pre-deploy hook, so the entrypoint runs this on every start; it only seeds
 * an empty table, so restarts never duplicate data.
 */
class SeedIfEmpty extends Command
{
    protected $signature = 'propmap:seed-if-empty';

    protected $description = 'Seed demo properties only when the properties table is empty';

    public function handle(): int
    {
        if (Property::query()->exists()) {
            $this->components->info('Properties already present, skipping seed.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
