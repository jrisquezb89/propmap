<?php

namespace App\Support;

use App\Enums\PropertyType;
use Faker\Generator;
use InvalidArgumentException;

/**
 * Hand-drawn Miami neighborhood data used by the factory and seeder.
 *
 * Polygons are deliberately drawn a little inland from shorelines so that
 * generated points never land in Biscayne Bay or the Atlantic. Each one is a
 * list of [lat, lng] vertices.
 *
 * price_per_sqft is a [min, max] range in USD loosely based on 2025-26 asking
 * prices; `listings` is how many properties the seeder creates there (sums to 400).
 * Optional size_factor shrinks homes where lots and housing stock are smaller.
 */
final class MiamiNeighborhoods
{
    public const ALL = [
        'Brickell' => [
            'polygon' => [[25.7695, -80.1975], [25.7695, -80.1915], [25.7600, -80.1915], [25.7535, -80.1955], [25.7535, -80.2010], [25.7640, -80.2020]],
            'price_per_sqft' => [650, 900],
            'types' => ['condo' => 85, 'townhouse' => 10, 'house' => 5],
            'listings' => 60,
            'city' => 'Miami', 'zip' => '33131',
            'streets' => ['Brickell Ave', 'Brickell Bay Dr', 'SW 1st Ave', 'S Miami Ave', 'SW 8th St', 'SW 10th St', 'SW 13th St'],
        ],
        'Downtown Miami' => [
            'polygon' => [[25.7860, -80.1990], [25.7860, -80.1905], [25.7710, -80.1905], [25.7710, -80.1990]],
            'price_per_sqft' => [550, 800],
            'types' => ['condo' => 92, 'townhouse' => 8],
            'listings' => 35,
            'city' => 'Miami', 'zip' => '33132',
            'streets' => ['Biscayne Blvd', 'NE 2nd Ave', 'N Miami Ave', 'NE 1st St', 'NE 4th St', 'NW 1st Ave'],
        ],
        'Wynwood' => [
            'polygon' => [[25.8080, -80.2040], [25.8080, -80.1970], [25.7960, -80.1970], [25.7960, -80.2040]],
            'price_per_sqft' => [500, 650],
            'types' => ['condo' => 50, 'townhouse' => 30, 'house' => 20],
            'size_factor' => 0.85,
            'listings' => 30,
            'city' => 'Miami', 'zip' => '33127',
            'streets' => ['NW 2nd Ave', 'NW 3rd Ave', 'NW 24th St', 'NW 26th St', 'NW 29th St', 'N Miami Ave'],
        ],
        'Edgewater' => [
            'polygon' => [[25.8150, -80.1915], [25.8150, -80.1875], [25.7985, -80.1870], [25.7985, -80.1915]],
            'price_per_sqft' => [550, 750],
            'types' => ['condo' => 90, 'townhouse' => 10],
            'listings' => 30,
            'city' => 'Miami', 'zip' => '33137',
            'streets' => ['Biscayne Blvd', 'NE 25th St', 'NE 27th St', 'NE 31st St', 'NE 4th Ave', 'NE 5th Ave'],
        ],
        'Little Havana' => [
            'polygon' => [[25.7740, -80.2350], [25.7740, -80.2050], [25.7600, -80.2050], [25.7600, -80.2350]],
            'price_per_sqft' => [350, 480],
            'types' => ['house' => 40, 'condo' => 40, 'townhouse' => 20],
            'size_factor' => 0.75,
            'listings' => 45,
            'city' => 'Miami', 'zip' => '33135',
            'streets' => ['SW 8th St', 'SW 7th St', 'SW 12th Ave', 'SW 17th Ave', 'SW 22nd Ave', 'SW 4th St'],
        ],
        'Coral Gables' => [
            'polygon' => [[25.7580, -80.2850], [25.7580, -80.2620], [25.7350, -80.2600], [25.7080, -80.2620], [25.7080, -80.2850]],
            'price_per_sqft' => [650, 1000],
            'types' => ['house' => 75, 'condo' => 15, 'townhouse' => 10],
            'listings' => 55,
            'city' => 'Coral Gables', 'zip' => '33134',
            'streets' => ['Alhambra Cir', 'Granada Blvd', 'Coral Way', 'Sevilla Ave', 'Valencia Ave', 'Almeria Ave', 'Segovia St'],
        ],
        'Coconut Grove' => [
            'polygon' => [[25.7400, -80.2550], [25.7400, -80.2400], [25.7300, -80.2380], [25.7180, -80.2450], [25.7180, -80.2550]],
            'price_per_sqft' => [700, 1000],
            'types' => ['house' => 55, 'townhouse' => 30, 'condo' => 15],
            'listings' => 35,
            'city' => 'Miami', 'zip' => '33133',
            'streets' => ['Main Hwy', 'Grand Ave', 'Tigertail Ave', 'Kumquat Ave', 'Douglas Rd', 'Aviation Ave'],
        ],
        'Miami Beach' => [
            'polygon' => [[25.8000, -80.1390], [25.8000, -80.1300], [25.7720, -80.1325], [25.7720, -80.1395]],
            'price_per_sqft' => [800, 1200],
            'types' => ['condo' => 80, 'townhouse' => 10, 'house' => 10],
            'listings' => 55,
            'city' => 'Miami Beach', 'zip' => '33139',
            'streets' => ['Collins Ave', 'Ocean Dr', 'Washington Ave', 'Meridian Ave', 'Euclid Ave', 'Pennsylvania Ave', 'Lenox Ave'],
        ],
        'Little Haiti' => [
            'polygon' => [[25.8450, -80.2000], [25.8450, -80.1880], [25.8200, -80.1880], [25.8200, -80.2000]],
            'price_per_sqft' => [350, 500],
            'types' => ['house' => 70, 'townhouse' => 20, 'condo' => 10],
            'size_factor' => 0.75,
            'listings' => 30,
            'city' => 'Miami', 'zip' => '33137',
            'streets' => ['NE 2nd Ave', 'NE 54th St', 'NE 59th St', 'NE 62nd St', 'N Miami Ave', 'NE 4th Ct'],
        ],
        'Allapattah' => [
            'polygon' => [[25.8150, -80.2280], [25.8150, -80.2080], [25.7900, -80.2080], [25.7900, -80.2280]],
            'price_per_sqft' => [300, 420],
            'types' => ['house' => 55, 'condo' => 30, 'townhouse' => 15],
            'size_factor' => 0.75,
            'listings' => 25,
            'city' => 'Miami', 'zip' => '33142',
            'streets' => ['NW 17th Ave', 'NW 20th St', 'NW 22nd Ave', 'NW 27th Ave', 'NW 36th St', 'NW 14th Ave'],
        ],
    ];

    /** Price-per-sqft adjustment by type: houses carry land value, townhouses trade a bit lower. */
    public const TYPE_PRICE_FACTOR = ['house' => 1.10, 'condo' => 1.00, 'townhouse' => 0.92];

    /** Curated Unsplash photos (free license), grouped by what they show. */
    public const IMAGES = [
        'house' => [
            '1568605114967-8130f3a36994', '1570129477492-45c003edd2be', '1600596542815-ffad4c1539a9',
            '1600585154340-be6161a56a0c', '1512917774080-9991f1c4c750', '1564013799919-ab600027ffc6',
            '1580587771525-78b9dba3b914', '1613490493576-7fde63acd811', '1605276374104-dee2a0ed3cd6',
            '1583608205776-bfd35f0d9f83', '1598228723793-52759bba239c', '1494526585095-c41746248156',
            '1599809275671-b5942cabc7a2', '1449844908441-8829872d2607', '1572120360610-d971b9d7767c',
        ],
        'condo' => [
            '1545324418-cc1a3fa10c00', '1502672260266-1c1ef2d93688', '1522708323590-d24dbb6b0267',
            '1493809842364-78817add7ffb', '1560448204-e02f11c3d0e2', '1484154218962-a197022b5858',
            '1486406146926-c627a92ad1ab', '1460317442991-0ec209397118', '1515263487990-61b07816b324',
            '1600607687939-ce8a6c25118c', '1600566753190-17f0baa2a6c3', '1600047509807-ba8f99d2cdde',
            '1600573472550-8090b5e0745e',
        ],
    ];

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::ALL);
    }

    public static function get(string $name): array
    {
        return self::ALL[$name] ?? throw new InvalidArgumentException("Unknown neighborhood [{$name}].");
    }

    /** Neighborhood name, weighted by its listing count. */
    public static function randomName(Generator $faker): string
    {
        return self::weightedPick($faker, array_map(fn (array $n) => $n['listings'], self::ALL));
    }

    public static function randomType(Generator $faker, string $name): PropertyType
    {
        return PropertyType::from(self::weightedPick($faker, self::get($name)['types']));
    }

    /**
     * Uniform random point inside the polygon via rejection sampling:
     * draw from the bounding box, keep the first draw that falls inside.
     *
     * @return array{0: float, 1: float} [lat, lng]
     */
    public static function randomPoint(Generator $faker, string $name): array
    {
        $polygon = self::get($name)['polygon'];
        $lats = array_column($polygon, 0);
        $lngs = array_column($polygon, 1);

        do {
            $lat = $faker->randomFloat(7, min($lats), max($lats));
            $lng = $faker->randomFloat(7, min($lngs), max($lngs));
        } while (! self::contains($polygon, $lat, $lng));

        return [$lat, $lng];
    }

    /** Ray-casting point-in-polygon test. */
    public static function contains(array $polygon, float $lat, float $lng): bool
    {
        $inside = false;
        $count = count($polygon);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$latI, $lngI] = $polygon[$i];
            [$latJ, $lngJ] = $polygon[$j];

            $crosses = ($latI > $lat) !== ($latJ > $lat)
                && $lng < ($lngJ - $lngI) * ($lat - $latI) / ($latJ - $latI) + $lngI;

            if ($crosses) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    public static function imageUrl(Generator $faker, PropertyType $type): string
    {
        $pool = self::IMAGES[$type === PropertyType::Condo ? 'condo' : 'house'];

        return 'https://images.unsplash.com/photo-'.$faker->randomElement($pool).'?auto=format&fit=crop&w=1200&q=70';
    }

    /** @param  array<string, int>  $weights */
    private static function weightedPick(Generator $faker, array $weights): string
    {
        $roll = $faker->numberBetween(1, array_sum($weights));

        foreach ($weights as $key => $weight) {
            if (($roll -= $weight) <= 0) {
                return $key;
            }
        }

        return array_key_last($weights);
    }
}
