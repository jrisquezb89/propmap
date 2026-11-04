<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Support\MiamiNeighborhoods;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Guards the seed data: every generated listing must sit inside its neighborhood (not in the bay). */
class PropertyFactoryTest extends TestCase
{
    #[Test]
    public function generated_coordinates_fall_inside_their_neighborhood(): void
    {
        foreach (MiamiNeighborhoods::names() as $name) {
            $polygon = MiamiNeighborhoods::get($name)['polygon'];

            foreach (Property::factory()->count(25)->inNeighborhood($name)->make() as $property) {
                $this->assertSame($name, $property->neighborhood);
                $this->assertTrue(
                    MiamiNeighborhoods::contains($polygon, $property->lat, $property->lng),
                    "{$property->lat},{$property->lng} is outside {$name}",
                );
            }
        }
    }

    #[Test]
    public function seeder_listing_counts_add_up_to_400(): void
    {
        $this->assertSame(400, array_sum(array_column(MiamiNeighborhoods::ALL, 'listings')));
    }

    #[Test]
    public function point_in_polygon_handles_inside_and_outside(): void
    {
        $square = [[0, 0], [0, 10], [10, 10], [10, 0]];

        $this->assertTrue(MiamiNeighborhoods::contains($square, 5, 5));
        $this->assertFalse(MiamiNeighborhoods::contains($square, 15, 5));
        $this->assertFalse(MiamiNeighborhoods::contains($square, 5, -1));
    }
}
