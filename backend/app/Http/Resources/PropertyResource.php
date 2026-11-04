<?php

namespace App\Http\Resources;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Property */
class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'sqft' => $this->sqft,
            'type' => $this->type->value,
            'address' => $this->address,
            'neighborhood' => $this->neighborhood,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'image_url' => $this->image_url,
            'listed_at' => $this->listed_at->toIso8601String(),
        ];
    }
}
