<?php

namespace App\Enums;

enum PropertyType: string
{
    case House = 'house';
    case Condo = 'condo';
    case Townhouse = 'townhouse';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
