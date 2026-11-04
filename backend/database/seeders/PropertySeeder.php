<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Support\MiamiNeighborhoods;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /** Fixed seed: every environment gets the same 400 listings (stable demos and screenshots). */
    private const FAKER_SEED = 2026;

    public function run(): void
    {
        fake()->seed(self::FAKER_SEED);

        foreach (MiamiNeighborhoods::ALL as $name => $config) {
            Property::factory()
                ->count($config['listings'])
                ->inNeighborhood($name)
                ->create();
        }
    }
}
