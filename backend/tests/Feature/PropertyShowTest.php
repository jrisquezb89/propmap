<?php

namespace Tests\Feature;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PropertyShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_single_property(): void
    {
        $property = Property::factory()->create();

        $this->getJson("/api/properties/{$property->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $property->id)
            ->assertJsonPath('data.title', $property->title)
            ->assertJsonPath('data.price', $property->price)
            ->assertJsonPath('data.type', $property->type->value)
            ->assertJsonPath('data.lat', $property->lat)
            ->assertJsonPath('data.lng', $property->lng);
    }

    #[Test]
    public function an_unknown_id_returns_a_json_404(): void
    {
        $this->getJson('/api/properties/999999')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function a_non_numeric_id_returns_404(): void
    {
        $this->getJson('/api/properties/abc')->assertNotFound();
    }
}
