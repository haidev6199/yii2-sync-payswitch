<?php

namespace Haidev6199\PaySwitch\Request;

/**
 * Giá trị CommonStatus theo collection hiện có.
 */
final class CommonStatus
{
    public const ACTIVE = 'ACTIVE';
    public const INACTIVE = 'INACTIVE';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [self::ACTIVE, self::INACTIVE];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
