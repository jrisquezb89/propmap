<?php

namespace Tests\Feature;

use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PropertyFiltersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_paginated_resource_collection(): void
    {
        Property::factory()->count(25)->create();

        $this->getJson('/api/properties')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'title', 'description', 'price', 'bedrooms', 'bathrooms', 'sqft', 'type',
                    'address', 'neighborhood', 'lat', 'lng', 'image_url', 'listed_at',
                ]],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function it_honours_per_page_and_keeps_filters_in_pagination_links(): void
    {
        Property::factory()->count(5)->create(['type' => PropertyType::Condo]);

        $response = $this->getJson('/api/properties?type=condo&per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.last_page', 3);

        $this->assertStringContainsString('type=condo', $response->json('links.next'));
    }

    #[Test]
    public function pagination_links_use_https_behind_a_tls_terminating_proxy(): void
    {
        Property::factory()->count(3)->create();

        $response = $this->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'])
            ->getJson('/api/properties?per_page=1')
            ->assertOk();

        $this->assertStringStartsWith('https://', $response->json('links.next'));
    }

    #[Test]
    public function it_orders_newest_listings_first(): void
    {
        $old = Property::factory()->create(['listed_at' => now()->subDays(30)]);
        $new = Property::factory()->create(['listed_at' => now()->subDay()]);

        $this->getJson('/api/properties')
            ->assertOk()
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.1.id', $old->id);
    }

    #[Test]
    public function it_filters_by_price_range_inclusively(): void
    {
        $cheap = Property::factory()->create(['price' => 300_000]);
        $atMin = Property::factory()->create(['price' => 500_000]);
        $atMax = Property::factory()->create(['price' => 900_000]);
        $pricey = Property::factory()->create(['price' => 2_000_000]);

        $this->assertIds('/api/properties?min_price=500000&max_price=900000', [$atMin, $atMax]);
        $this->assertIds('/api/properties?min_price=900000', [$atMax, $pricey]);
        $this->assertIds('/api/properties?max_price=500000', [$cheap, $atMin]);
    }

    #[Test]
    public function it_filters_by_minimum_bedrooms(): void
    {
        $studio = Property::factory()->create(['bedrooms' => 0]);
        $two = Property::factory()->create(['bedrooms' => 2]);
        $three = Property::factory()->create(['bedrooms' => 3]);
        $five = Property::factory()->create(['bedrooms' => 5]);

        $this->assertIds('/api/properties?bedrooms=3', [$three, $five]);
        $this->assertIds('/api/properties?bedrooms=0', [$studio, $two, $three, $five]);
    }

    #[Test]
    public function it_filters_by_type(): void
    {
        $house = Property::factory()->create(['type' => PropertyType::House]);
        Property::factory()->create(['type' => PropertyType::Condo]);
        $townhouse = Property::factory()->create(['type' => PropertyType::Townhouse]);

        $this->assertIds('/api/properties?type=house', [$house]);
        $this->assertIds('/api/properties?type=townhouse', [$townhouse]);
    }

    #[Test]
    public function it_combines_filters_with_and_semantics(): void
    {
        $match = Property::factory()->create(['type' => PropertyType::House, 'bedrooms' => 4, 'price' => 1_200_000]);
        Property::factory()->create(['type' => PropertyType::Condo, 'bedrooms' => 4, 'price' => 1_200_000]);
        Property::factory()->create(['type' => PropertyType::House, 'bedrooms' => 2, 'price' => 1_200_000]);
        Property::factory()->create(['type' => PropertyType::House, 'bedrooms' => 4, 'price' => 3_000_000]);

        $this->assertIds('/api/properties?type=house&bedrooms=3&min_price=1000000&max_price=1500000', [$match]);
    }

    #[Test]
    public function it_returns_an_empty_page_when_nothing_matches(): void
    {
        Property::factory()->count(3)->create(['price' => 500_000]);

        $this->getJson('/api/properties?min_price=10000000')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    #[Test]
    #[DataProvider('invalidQueries')]
    public function it_rejects_invalid_filters(string $query, string $field): void
    {
        $this->getJson('/api/properties?'.$query)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function invalidQueries(): array
    {
        return [
            'unknown type' => ['type=castle', 'type'],
            'negative price' => ['min_price=-1', 'min_price'],
            'non-numeric price' => ['max_price=cheap', 'max_price'],
            'min above max' => ['min_price=900000&max_price=100000', 'max_price'],
            'negative bedrooms' => ['bedrooms=-1', 'bedrooms'],
            'per_page above cap' => ['per_page=101', 'per_page'],
            'per_page zero' => ['per_page=0', 'per_page'],
        ];
    }

    /** @param  list<Property>  $expected */
    private function assertIds(string $uri, array $expected): void
    {
        $ids = $this->getJson($uri)->assertOk()->json('data.*.id');

        $this->assertEqualsCanonicalizing(array_map(fn (Property $p) => $p->id, $expected), $ids, "Unexpected results for {$uri}");
    }
}
