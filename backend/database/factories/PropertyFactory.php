<?php

namespace Database\Factories;

use App\Enums\PropertyType;
use App\Models\Property;
use App\Support\MiamiNeighborhoods;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /** Bedroom-count weights and sqft range per bedroom count, by type. */
    private const LAYOUTS = [
        'condo' => [
            'bedrooms' => [0 => 8, 1 => 35, 2 => 40, 3 => 17],
            'sqft' => [0 => [450, 650], 1 => [650, 950], 2 => [950, 1400], 3 => [1300, 2200]],
        ],
        'townhouse' => [
            'bedrooms' => [2 => 30, 3 => 50, 4 => 20],
            'sqft' => [2 => [1100, 1500], 3 => [1400, 2100], 4 => [1900, 2800]],
        ],
        'house' => [
            'bedrooms' => [2 => 10, 3 => 35, 4 => 35, 5 => 15, 6 => 5],
            'sqft' => [2 => [1100, 1600], 3 => [1500, 2400], 4 => [2200, 3400], 5 => [3000, 4500], 6 => [4000, 6000]],
        ],
    ];

    private const ADJECTIVES = ['Bright', 'Modern', 'Renovated', 'Spacious', 'Charming', 'Sun-filled', 'Elegant', 'Updated', 'Stylish', 'Cozy'];

    private const FEATURES = [
        'condo' => ['floor-to-ceiling windows', 'a private balcony', 'bay views', 'a resort-style pool deck', '24-hour concierge', 'a fully equipped gym', 'assigned parking', 'quartz countertops', 'an in-unit washer and dryer'],
        'townhouse' => ['a private patio', 'an attached garage', 'impact windows', 'a rooftop terrace', 'an open-concept kitchen', 'low HOA fees', 'a gated entrance'],
        'house' => ['a private pool', 'a fenced backyard', 'impact windows and doors', 'a two-car garage', 'mature tropical landscaping', 'a covered terrace', 'a renovated kitchen', 'a new roof'],
    ];

    public function definition(): array
    {
        return $this->attributesFor(MiamiNeighborhoods::randomName($this->faker));
    }

    /** All neighborhood-dependent fields (location, price, address) are regenerated together. */
    public function inNeighborhood(string $name): static
    {
        return $this->state(fn () => $this->attributesFor($name));
    }

    private function attributesFor(string $neighborhood): array
    {
        $faker = $this->faker;
        $config = MiamiNeighborhoods::get($neighborhood);
        $type = MiamiNeighborhoods::randomType($faker, $neighborhood);
        $layout = self::LAYOUTS[$type->value];

        $bedrooms = $this->weighted($layout['bedrooms']);
        $sqft = (int) round($faker->numberBetween(...$layout['sqft'][$bedrooms]) * ($config['size_factor'] ?? 1.0), -1);
        $bathrooms = max(1.0, $bedrooms - 1 + $faker->randomElement([0, 0.5, 1, 1]));

        // price = sqft x neighborhood $/sqft x type factor x +/-15% noise, rounded to $1k
        $pricePerSqft = $faker->numberBetween(...$config['price_per_sqft']);
        $noise = $faker->randomFloat(3, 0.85, 1.15);
        $price = (int) round($sqft * $pricePerSqft * MiamiNeighborhoods::TYPE_PRICE_FACTOR[$type->value] * $noise, -3);

        [$lat, $lng] = MiamiNeighborhoods::randomPoint($faker, $neighborhood);

        return [
            'title' => $this->title($type, $bedrooms, $neighborhood),
            'description' => $this->description($type, $neighborhood),
            'price' => $price,
            'bedrooms' => $bedrooms,
            'bathrooms' => $bathrooms,
            'sqft' => $sqft,
            'type' => $type,
            'address' => $this->address($type, $config),
            'neighborhood' => $neighborhood,
            'lat' => $lat,
            'lng' => $lng,
            'image_url' => MiamiNeighborhoods::imageUrl($faker, $type),
            'listed_at' => $faker->dateTimeBetween('-120 days', 'now'),
        ];
    }

    private function title(PropertyType $type, int $bedrooms, string $neighborhood): string
    {
        $size = $bedrooms === 0 ? 'Studio' : "{$bedrooms}BR";

        return sprintf('%s %s %s in %s', $this->faker->randomElement(self::ADJECTIVES), $size, ucfirst($type->value), $neighborhood);
    }

    private function description(PropertyType $type, string $neighborhood): string
    {
        [$a, $b, $c] = $this->faker->randomElements(self::FEATURES[$type->value], 3);

        return sprintf(
            '%s %s %s in the heart of %s, featuring %s and %s. Also includes %s. Close to dining, shops and transit.',
            $this->faker->randomElement(['Welcome to this', 'Discover this', "Don't miss this"]),
            strtolower($this->faker->randomElement(self::ADJECTIVES)),
            $type->value,
            $neighborhood,
            $a,
            $b,
            $c,
        );
    }

    private function address(PropertyType $type, array $config): string
    {
        $street = $this->faker->numberBetween(100, 3999).' '.$this->faker->randomElement($config['streets']);

        if ($type === PropertyType::Condo) {
            $floor = $this->faker->numberBetween(2, 45);
            $street .= ', Unit '.$floor.str_pad((string) $this->faker->numberBetween(1, 12), 2, '0', STR_PAD_LEFT);
        }

        return "{$street}, {$config['city']}, FL {$config['zip']}";
    }

    /** @param  array<int, int>  $weights */
    private function weighted(array $weights): int
    {
        $roll = $this->faker->numberBetween(1, array_sum($weights));

        foreach ($weights as $value => $weight) {
            if (($roll -= $weight) <= 0) {
                return $value;
            }
        }

        return array_key_last($weights);
    }
}
