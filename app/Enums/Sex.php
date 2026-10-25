<?php

namespace App\Enums;

/**
 * CIMA-050: patient sex. Displayed on the public ward page (ADR-007), so
 * the label is human text, never the raw value.
 */
enum Sex: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
            self::Other => 'Other',
        };
    }
}
