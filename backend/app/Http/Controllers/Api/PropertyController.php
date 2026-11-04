<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyIndexRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function index(PropertyIndexRequest $request): AnonymousResourceCollection
    {
        $properties = Property::query()
            ->priceBetween($request->minPrice(), $request->maxPrice())
            ->minBedrooms($request->bedrooms())
            ->ofType($request->propertyType())
            ->withinBounds($request->bounds())
            ->orderByDesc('listed_at')
            ->orderByDesc('id') // tie-breaker keeps pages stable
            ->paginate($request->perPage())
            ->withQueryString();

        return PropertyResource::collection($properties);
    }

    public function show(Property $property): PropertyResource
    {
        return new PropertyResource($property);
    }
}
