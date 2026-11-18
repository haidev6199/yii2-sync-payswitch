<?php

namespace Haidev6199\PaySwitch\Config;

use Haidev6199\PaySwitch\Exceptions\ConfigurationException;

/**
 * Cấu hình kết nối PaySwitch. Không chứa giá trị mặc định nhạy cảm; base URL bắt buộc đọc từ env hoặc config.
 */
final class PaySwitchConfig
{
    public const ENV_BASE_URL = 'PAYSWITCH_BASE_URL';
    public const ENV_TIMEOUT = 'PAYSWITCH_TIMEOUT';
    public const ENV_CONNECT_TIMEOUT = 'PAYSWITCH_CONNECT_TIMEOUT';
    public const ENV_SSL_VERIFY = 'PAYSWITCH_SSL_VERIFY';
    public const ENV_RETRY_READS = 'PAYSWITCH_RETRY_READS';

    public const DEFAULT_TIMEOUT = 30;
    public const DEFAULT_CONNECT_TIMEOUT = 5;

    /** @var string */
    private $baseUrl;
    /** @var int */
    private $timeout;
    /** @var int */
    private $connectTimeout;
    /** @var bool */
    private $sslVerify;
    /** @var bool */
    private $retryReadOperations;

    public function __construct(
        string $baseUrl,
        int $timeout = self::DEFAULT_TIMEOUT,
        int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        bool $sslVerify = true,
        bool $retryReadOperations = true
    ) {
        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            throw new ConfigurationException('Thiếu cấu hình base URL (PAYSWITCH_BASE_URL).');
        }

        $parts = parse_url($baseUrl);
        if (
            !is_array($parts)
            || !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
        ) {
            throw new ConfigurationException('Base URL không hợp lệ, yêu cầu dạng http(s)://host[:port].');
        }

        if ($timeout <= 0 || $connectTimeout <= 0) {
            throw new ConfigurationException('Timeout và connect timeout phải lớn hơn 0.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->sslVerify = $sslVerify;
        $this->retryReadOperations = $retryReadOperations;
    }

    /**
     * Đọc cấu hình từ biến môi trường. Giá trị trong $overrides (khác null) được ưu tiên hơn env.
     *
     * @param array<string, mixed> $overrides khóa: baseUrl, timeout, connectTimeout, sslVerify, retryReadOperations
     */
    public static function fromEnv(array $overrides = []): self
    {
        $baseUrl = self::pick($overrides, 'baseUrl', self::envValue(self::ENV_BASE_URL));
        $timeout = self::pick($overrides, 'timeout', self::envValue(self::ENV_TIMEOUT));
        $connectTimeout = self::pick($overrides, 'connectTimeout', self::envValue(self::ENV_CONNECT_TIMEOUT));
        $sslVerify = self::pick($overrides, 'sslVerify', self::envValue(self::ENV_SSL_VERIFY));
        $retry = self::pick($overrides, 'retryReadOperations', self::envValue(self::ENV_RETRY_READS));

        return new self(
            (string) $baseUrl,
            (int) ($timeout ?? self::DEFAULT_TIMEOUT),
            (int) ($connectTimeout ?? self::DEFAULT_CONNECT_TIMEOUT),
            self::toBool($sslVerify, true),
            self::toBool($retry, true)
        );
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    public function isSslVerify(): bool
    {
        return $this->sslVerify;
    }

    public function isRetryReadOperations(): bool
    {
        return $this->retryReadOperations;
    }

    public function url(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * @param array<string, mixed> $overrides
     * @param mixed                $fallback
     *
     * @return mixed
     */
    private static function pick(array $overrides, string $key, $fallback)
    {
        if (isset($overrides[$key])) {
            return $overrides[$key];
        }

        return $fallback;
    }

    /**
     * @return string|null
     */
    private static function envValue(string $name)
    {
        $value = getenv($name);
        if ($value === false || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * @param mixed $value
     */
    private static function toBool($value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }

        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $result === null ? $default : $result;
    }
}
