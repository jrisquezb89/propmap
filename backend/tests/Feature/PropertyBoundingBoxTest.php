<?php

namespace Tests\Feature;

use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PropertyBoundingBoxTest extends TestCase
{
    use RefreshDatabase;

    /** A viewport roughly covering Brickell. */
    private const BOX = ['north' => 25.7700, 'south' => 25.7500, 'east' => -80.1900, 'west' => -80.2050];

    #[Test]
    public function it_returns_only_properties_inside_the_box(): void
    {
        $inside = $this->propertyAt(25.7600, -80.1950);
        $this->propertyAt(25.7900, -80.1950); // north of the box (Wynwood-ish)
        $this->propertyAt(25.7300, -80.1950); // south
        $this->propertyAt(25.7600, -80.1300); // east (Miami Beach)
        $this->propertyAt(25.7600, -80.2700); // west (Coral Gables)

        $this->assertIds(self::BOX, [$inside]);
    }

    #[Test]
    public function edges_are_inclusive(): void
    {
        $onNorth = $this->propertyAt(self::BOX['north'], -80.1950);
        $onSouth = $this->propertyAt(self::BOX['south'], -80.1950);
        $onEast = $this->propertyAt(25.7600, self::BOX['east']);
        $onWest = $this->propertyAt(25.7600, self::BOX['west']);
        $this->propertyAt(self::BOX['north'] + 0.0000001, -80.1950); // just outside

        $this->assertIds(self::BOX, [$onNorth, $onSouth, $onEast, $onWest]);
    }

    #[Test]
    public function it_combines_the_box_with_other_filters(): void
    {
        $match = $this->propertyAt(25.7600, -80.1950, ['type' => PropertyType::Condo, 'price' => 800_000]);
        $this->propertyAt(25.7610, -80.1960, ['type' => PropertyType::House, 'price' => 800_000]);
        $this->propertyAt(25.7620, -80.1970, ['type' => PropertyType::Condo, 'price' => 3_000_000]);
        $this->propertyAt(25.7900, -80.1950, ['type' => PropertyType::Condo, 'price' => 800_000]); // outside

        $this->assertIds([...self::BOX, 'type' => 'condo', 'max_price' => 1_000_000], [$match]);
    }

    #[Test]
    public function a_box_covering_everything_matches_the_unfiltered_total(): void
    {
        Property::factory()->count(30)->create(); // spread across all Miami neighborhoods

        $this->getJson('/api/properties?'.http_build_query(['north' => 26, 'south' => 25.5, 'east' => -80, 'west' => -80.5]))
            ->assertOk()
            ->assertJsonPath('meta.total', 30);
    }

    #[Test]
    #[DataProvider('incompleteBoxes')]
    public function a_partial_box_is_rejected(array $params, array $missing): void
    {
        $this->getJson('/api/properties?'.http_build_query($params))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($missing);
    }

    public static function incompleteBoxes(): array
    {
        return [
            'only north' => [['north' => 25.77], ['south', 'east', 'west']],
            'missing west' => [['north' => 25.77, 'south' => 25.75, 'east' => -80.19], ['west']],
        ];
    }

    #[Test]
    public function south_above_north_is_rejected(): void
    {
        $this->getJson('/api/properties?'.http_build_query([...self::BOX, 'north' => 25.70, 'south' => 25.80]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('south');
    }

    #[Test]
    #[DataProvider('outOfRangeEdges')]
    public function out_of_range_coordinates_are_rejected(string $edge, float $value): void
    {
        $this->getJson('/api/properties?'.http_build_query([...self::BOX, $edge => $value]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($edge);
    }

    public static function outOfRangeEdges(): array
    {
        return [
            'north > 90' => ['north', 91],
            'south < -90' => ['south', -91],
            'east > 180' => ['east', 181],
            'west < -180' => ['west', -181],
        ];
    }

    private function propertyAt(float $lat, float $lng, array $attributes = []): Property
    {
        return Property::factory()->create(['lat' => $lat, 'lng' => $lng, ...$attributes]);
    }

    /** @param  list<Property>  $expected */
    private function assertIds(array $params, array $expected): void
    {
        $ids = $this->getJson('/api/properties?'.http_build_query($params))->assertOk()->json('data.*.id');

        $this->assertEqualsCanonicalizing(array_map(fn (Property $p) => $p->id, $expected), $ids);
    }
}
