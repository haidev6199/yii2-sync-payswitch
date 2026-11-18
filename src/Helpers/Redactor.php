<?php

namespace Haidev6199\PaySwitch\Helpers;

/**
 * Che các field nhạy cảm trước khi đưa dữ liệu vào bất kỳ output debug nào.
 */
final class Redactor
{
    public const MASK = '***';

    private const SENSITIVE_KEYS = ['password', 'pin', 'newpin', 'token', 'secret', 'apikey'];

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public static function redact(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $result[$key] = self::MASK;
            } elseif (is_array($value)) {
                $result[$key] = self::redact($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
