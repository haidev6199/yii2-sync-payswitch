<?php

namespace Haidev6199\PaySwitch\Request\Reader;

/**
 * Giá trị configType cho reset-config-flags theo collection hiện có.
 */
final class ConfigType
{
    public const EMV_CONFIG = 'EMV_CONFIG';
    public const CAPK = 'CAPK';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [self::EMV_CONFIG, self::CAPK];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
