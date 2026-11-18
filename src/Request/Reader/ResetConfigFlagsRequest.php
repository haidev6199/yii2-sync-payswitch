<?php

namespace Haidev6199\PaySwitch\Request\Reader;

use Haidev6199\PaySwitch\Request\AbstractRequest;

/**
 * POST /api/reader/reset-config-flags
 *
 * Phải truyền đúng một trong hai: serialNumber, hoặc allReaders = true (không kết hợp).
 * allReaders = false được chấp nhận và không gửi lên server; khi đó serialNumber là bắt buộc.
 * configType bỏ trống thì không gửi field này (reset cả 2 cờ).
 */
final class ResetConfigFlagsRequest extends AbstractRequest
{
    private $serialNumber;
    private $allReaders;
    private $configType;

    public function __construct(?string $serialNumber = null, bool $allReaders = false, ?string $configType = null)
    {
        $this->serialNumber = $serialNumber;
        $this->allReaders = $allReaders;
        $this->configType = $configType;
    }

    public function path(): string
    {
        return '/api/reader/reset-config-flags';
    }

    public function toArray(): array
    {
        $payload = $this->allReaders
            ? ['allReaders' => true]
            : ['serialNumber' => $this->serialNumber];

        if (!self::isBlank($this->configType)) {
            $payload['configType'] = $this->configType;
        }

        return $payload;
    }

    protected function rules(): array
    {
        $hasSerial = !self::isBlank($this->serialNumber);
        $errors = [];

        if ($hasSerial && $this->allReaders) {
            $errors[] = 'Không được truyền đồng thời serialNumber và allReaders=true.';
        } elseif (!$hasSerial && !$this->allReaders) {
            $errors[] = 'Phải truyền serialNumber hoặc allReaders=true.';
        }

        if (!self::isBlank($this->configType) && !ConfigType::isValid((string) $this->configType)) {
            $errors[] = 'configType phải là ' . implode(' hoặc ', ConfigType::values()) . ' (hoặc để trống).';
        }

        return $errors;
    }
}
