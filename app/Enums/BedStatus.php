<?php

namespace App\Enums;

/**
 * CIMA-045 / cima-database #8: bed operational states as a PHP backed
 * string enum — never collapse them with treatment/encounter statuses.
 *
 * Bed state machine (docs/IMPLEMENTATION_PLAN.md §3):
 *   available → occupied → cleaning → available | out_of_service.
 *
 * `occupied` is reserved for the allocation workflow (CIMA-060); bed
 * management may only move beds between the manual states (AVAILABLE,
 * CLEANING, OUT_OF_SERVICE) and may not enter or leave OCCUPIED directly.
 */
enum BedStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Cleaning = 'cleaning';
    case OutOfService = 'out_of_service';

    public function label(): string
    {
        return match ($this) {
            self::Available => __('Available'),
            self::Occupied => __('Occupied'),
            self::Cleaning => __('Cleaning'),
            self::OutOfService => __('Out of service'),
        };
    }

    /** States the bed-management module may set directly (CIMA-045). */
    /** @return list<self> */
    public static function manualStates(): array
    {
        return [
            self::Available,
            self::Cleaning,
            self::OutOfService,
        ];
    }

    /** @return list<array{value: string, label: string}> for UI selects. */
    public static function manualOptions(): array
    {
        $options = [];

        foreach (self::manualStates() as $status) {
            $options[] = [
                'value' => $status->value,
                'label' => $status->label(),
            ];
        }

        return $options;
    }

    /** Legal targets from the state machine; CIMA-060 extends from Occupied. */
    /** @return list<self> */
    public function allowedTargets(): array
    {
        return match ($this) {
            self::Available => [self::Occupied, self::Cleaning, self::OutOfService],
            self::Occupied => [self::Cleaning],
            self::Cleaning => [self::Available, self::OutOfService],
            self::OutOfService => [self::Available, self::Cleaning],
        };
    }
}
