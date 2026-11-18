<?php

namespace Haidev6199\PaySwitch\Tests\Unit;

use Haidev6199\PaySwitch\Config\PaySwitchConfig;
use Haidev6199\PaySwitch\Exceptions\ConfigurationException;
use PHPUnit\Framework\TestCase;

final class PaySwitchConfigTest extends TestCase
{
    private const ENV_KEYS = [
        'PAYSWITCH_BASE_URL',
        'PAYSWITCH_TIMEOUT',
        'PAYSWITCH_CONNECT_TIMEOUT',
        'PAYSWITCH_SSL_VERIFY',
        'PAYSWITCH_RETRY_READS',
    ];

    /** @var array<string, string|false> */
    private $backup = [];

    protected function setUp(): void
    {
        foreach (self::ENV_KEYS as $key) {
            $this->backup[$key] = getenv($key);
            putenv($key . '=');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->backup as $key => $value) {
            putenv($value === false ? $key . '=' : $key . '=' . $value);
        }
    }

    public function testEmptyBaseUrlIsRejected(): void
    {
        $this->expectException(ConfigurationException::class);
        new PaySwitchConfig('');
    }

    public function testBaseUrlWithoutHttpSchemeIsRejected(): void
    {
        $this->expectException(ConfigurationException::class);
        new PaySwitchConfig('ftp://10.0.0.5');
    }

    public function testNonPositiveTimeoutIsRejected(): void
    {
        $this->expectException(ConfigurationException::class);
        new PaySwitchConfig('http://10.0.0.5:8080', 0);
    }

    public function testTrailingSlashIsRemovedFromUrl(): void
    {
        $config = new PaySwitchConfig('http://10.0.0.5:8080/');

        self::assertSame('http://10.0.0.5:8080/api/merchant/get-merchant', $config->url('/api/merchant/get-merchant'));
    }

    public function testDefaultsAreAppliedWhenNotProvided(): void
    {
        $config = new PaySwitchConfig('https://payswitch.internal');

        self::assertSame(30, $config->getTimeout());
        self::assertSame(5, $config->getConnectTimeout());
        self::assertTrue($config->isSslVerify());
        self::assertTrue($config->isRetryReadOperations());
    }

    public function testFromEnvReadsVariables(): void
    {
        putenv('PAYSWITCH_BASE_URL=http://10.0.0.7:8080');
        putenv('PAYSWITCH_TIMEOUT=12');
        putenv('PAYSWITCH_SSL_VERIFY=false');

        $config = PaySwitchConfig::fromEnv();

        self::assertSame('http://10.0.0.7:8080', $config->getBaseUrl());
        self::assertSame(12, $config->getTimeout());
        self::assertFalse($config->isSslVerify());
    }

    public function testFromEnvOverridesWinOverEnvironment(): void
    {
        putenv('PAYSWITCH_BASE_URL=http://10.0.0.7:8080');

        $config = PaySwitchConfig::fromEnv(['baseUrl' => 'http://10.0.0.9:9090']);

        self::assertSame('http://10.0.0.9:9090', $config->getBaseUrl());
    }

    public function testFromEnvWithoutBaseUrlFails(): void
    {
        $this->expectException(ConfigurationException::class);
        PaySwitchConfig::fromEnv();
    }
}
