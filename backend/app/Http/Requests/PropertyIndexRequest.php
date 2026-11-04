<?php

namespace App\Http\Requests;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PropertyIndexRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 100;

    private const BOUNDS = ['north', 'south', 'east', 'west'];

    public function rules(): array
    {
        // A bounding box is all four edges or none.
        $boundsRule = 'required_with:'.implode(',', self::BOUNDS);

        return [
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'type' => ['nullable', Rule::enum(PropertyType::class)],
            'north' => [$boundsRule, 'numeric', 'between:-90,90'],
            'south' => [$boundsRule, 'numeric', 'between:-90,90'],
            'east' => [$boundsRule, 'numeric', 'between:-180,180'],
            'west' => [$boundsRule, 'numeric', 'between:-180,180'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        $message = 'A bounding box needs all four edges: north, south, east and west.';

        return array_fill_keys(array_map(fn (string $edge) => "{$edge}.required_with", self::BOUNDS), $message);
    }

    /** Cross-field checks that only make sense once each field is individually valid. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $min = $this->input('min_price');
                $max = $this->input('max_price');
                if ($min !== null && $max !== null && (int) $min > (int) $max) {
                    $validator->errors()->add('max_price', 'The max price must be greater than or equal to the min price.');
                }

                if ($this->hasBounds() && (float) $this->input('south') > (float) $this->input('north')) {
                    $validator->errors()->add('south', 'The south edge must be less than or equal to the north edge.');
                }
            },
        ];
    }

    public function minPrice(): ?int
    {
        return $this->filled('min_price') ? $this->integer('min_price') : null;
    }

    public function maxPrice(): ?int
    {
        return $this->filled('max_price') ? $this->integer('max_price') : null;
    }

    public function bedrooms(): ?int
    {
        return $this->filled('bedrooms') ? $this->integer('bedrooms') : null;
    }

    public function propertyType(): ?PropertyType
    {
        return $this->enum('type', PropertyType::class);
    }

    /** @return array{north: float, south: float, east: float, west: float}|null */
    public function bounds(): ?array
    {
        if (! $this->hasBounds()) {
            return null;
        }

        return array_map(fn (string $edge) => $this->float($edge), array_combine(self::BOUNDS, self::BOUNDS));
    }

    public function perPage(): int
    {
        return $this->filled('per_page') ? $this->integer('per_page') : self::DEFAULT_PER_PAGE;
    }

    private function hasBounds(): bool
    {
        return $this->filled(self::BOUNDS);
    }
}
