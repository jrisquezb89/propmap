<?php

namespace App\Models;

use App\Enums\PropertyType;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'price', 'bedrooms', 'bathrooms', 'sqft', 'type',
        'address', 'neighborhood', 'lat', 'lng', 'image_url', 'listed_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'bedrooms' => 'integer',
            'bathrooms' => 'float',
            'sqft' => 'integer',
            'type' => PropertyType::class,
            'lat' => 'float',
            'lng' => 'float',
            'listed_at' => 'immutable_datetime',
        ];
    }

    #[Scope]
    protected function priceBetween(Builder $query, ?int $min, ?int $max): void
    {
        $query
            ->when($min !== null, fn (Builder $q) => $q->where('price', '>=', $min))
            ->when($max !== null, fn (Builder $q) => $q->where('price', '<=', $max));
    }

    /** "3+" semantics, like listing sites: at least N bedrooms. */
    #[Scope]
    protected function minBedrooms(Builder $query, ?int $bedrooms): void
    {
        $query->when($bedrooms !== null, fn (Builder $q) => $q->where('bedrooms', '>=', $bedrooms));
    }

    #[Scope]
    protected function ofType(Builder $query, ?PropertyType $type): void
    {
        $query->when($type !== null, fn (Builder $q) => $q->where('type', $type->value));
    }

    /**
     * Map viewport filter. Two range conditions, served by the (lat, lng) index.
     * Edges are inclusive. Boxes crossing the antimeridian (west > east) are not supported.
     */
    #[Scope]
    protected function withinBounds(Builder $query, ?array $bounds): void
    {
        if ($bounds === null) {
            return;
        }

        $query
            ->whereBetween('lat', [$bounds['south'], $bounds['north']])
            ->whereBetween('lng', [$bounds['west'], $bounds['east']]);
    }
}
