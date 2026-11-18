<?php

namespace Haidev6199\PaySwitch\Tests\Integration;

use Haidev6199\PaySwitch\Client\PaySwitchClient;
use Haidev6199\PaySwitch\Config\PaySwitchConfig;
use Haidev6199\PaySwitch\Exceptions\BusinessException;
use Haidev6199\PaySwitch\Request\AbstractRequest;
use Haidev6199\PaySwitch\Request\Merchant\AddAcquirerRequest;
use Haidev6199\PaySwitch\Request\Merchant\AddMerchantRequest;
use Haidev6199\PaySwitch\Request\Merchant\GetMerchantRequest;
use Haidev6199\PaySwitch\Request\Merchant\UpdateMerchantRequest;
use Haidev6199\PaySwitch\Request\MidTid\AddAcquirerTidRequest;
use Haidev6199\PaySwitch\Request\MidTid\AddSysTidRequest;
use Haidev6199\PaySwitch\Request\MidTid\ChangeSysTidPinRequest;
use Haidev6199\PaySwitch\Request\MidTid\GetAcquirerTidRequest;
use Haidev6199\PaySwitch\Request\MidTid\GetSysTidRequest;
use Haidev6199\PaySwitch\Request\MidTid\UpdateSysTidStatusRequest;
use Haidev6199\PaySwitch\Request\Reader\AddReaderRequest;
use Haidev6199\PaySwitch\Request\Reader\GetReaderRequest;
use Haidev6199\PaySwitch\Request\Reader\ResetConfigFlagsRequest;
use Haidev6199\PaySwitch\Tests\Support\MockTransport;
use PHPUnit\Framework\TestCase;

/**
 * Kiểm tra từng endpoint trong Postman collection "Checkout VCB" bằng mock HTTP.
 */
final class EndpointsTest extends TestCase
{
    private const BASE = 'http://payswitch.test';

    /**
     * @return array<string, array{0: AbstractRequest, 1: string, 2: string[]}>
     */
    public function endpointProvider(): array
    {
        return [
            'add-merchant' => [
                new AddMerchantRequest('merchant01', 'Passw0rd!', 'Cua Hang ABC', 'ABC', 'Ho Chi Minh', 'Phuong Ben Nghe', '123 Nguyen Hue', 'merchant01@example.com', '0912345678'),
                '/api/merchant/add-merchant',
                ['username', 'password', 'merchantName', 'shortName', 'email', 'phone', 'city', 'ward', 'address'],
            ],
            'get-merchant' => [
                new GetMerchantRequest(42),
                '/api/merchant/get-merchant',
                ['merchantId'],
            ],
            'update-merchant' => [
                new UpdateMerchantRequest(42, 'Cua Hang ABC - Updated', 'merchant01@example.com', '0912345678', 'Ho Chi Minh', 'Phuong Sai Gon', '456 Le Loi'),
                '/api/merchant/update-merchant',
                ['merchantId', 'merchantName', 'email', 'phone', 'city', 'ward', 'address'],
            ],
            'add-acquirer' => [
                new AddAcquirerRequest('ACQ00000001', 'Ngan hang thanh toan', 'ACTIVE'),
                '/api/merchant/add-acquirer',
                ['name', 'description', 'status'],
            ],
            'add-reader' => [
                new AddReaderRequest('SN0000000001', 'ACTIVE', 'PAX_A920', 42),
                '/api/reader/add-reader',
                ['serialNumber', 'status', 'readerType', 'merchantId'],
            ],
            'get-reader' => [
                new GetReaderRequest('SN0000000001'),
                '/api/reader/get-reader',
                ['serialNumber'],
            ],
            'reset-config-flags' => [
                new ResetConfigFlagsRequest(null, true, 'CAPK'),
                '/api/reader/reset-config-flags',
                ['allReaders', 'configType'],
            ],
            'add-sys-tid' => [
                new AddSysTidRequest('ST00000001', 'SM00000001', '123456', 42, '123 Nguyen Hue', 'T00000001', 'M00000001', 'ACQ00000001', 'VND'),
                '/api/mid-tid/add-sys-tid',
                ['sysTid', 'sysMid', 'pin', 'merchantId', 'address', 'acquirerTid', 'acquirerMid', 'acquirer', 'currency'],
            ],
            'add-acquirer-tid' => [
                new AddAcquirerTidRequest('ST00000001', 'U00000002', 'M00000001', 'ACQ00000001', 'ACTIVE', 'VND'),
                '/api/mid-tid/add-acquirer-tid',
                ['sysTid', 'tid', 'mid', 'acquirer', 'status', 'currency'],
            ],
            'get-acquirer-tid' => [
                new GetAcquirerTidRequest('U00000002', 'ACQ00000001'),
                '/api/mid-tid/get-acquirer-tid',
                ['tid', 'acquirer'],
            ],
            'get-sys-tid' => [
                new GetSysTidRequest('ST00000001'),
                '/api/mid-tid/get-sys-tid',
                ['sysTid'],
            ],
            'change-sys-tid-pin' => [
                new ChangeSysTidPinRequest('ST00000001', '654321'),
                '/api/mid-tid/change-sys-tid-pin',
                ['sysTid', 'newPin'],
            ],
            'update-sys-tid-status' => [
                new UpdateSysTidStatusRequest('ST00000001', 'INACTIVE'),
                '/api/mid-tid/update-sys-tid-status',
                ['sysTid', 'status'],
            ],
        ];
    }

    /**
     * @dataProvider endpointProvider
     *
     * @param string[] $expectedKeys
     */
    public function testEndpointSendsExpectedRequestAndParsesSuccess(AbstractRequest $request, string $path, array $expectedKeys): void
    {
        $transport = (new MockTransport())->respondJson(200, [
            'code' => '200',
            'message' => 'Success',
            'data' => ['id' => 1],
        ]);
        $client = new PaySwitchClient(new PaySwitchConfig(self::BASE), $transport);

        $response = $client->send($request);

        self::assertTrue($response->success);
        self::assertSame(['id' => 1], $response->data['data']);

        $call = $transport->calls[0];
        self::assertSame('POST', $call['method']);
        self::assertSame(self::BASE . $path, $call['url']);
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $call['payload'], 'Thiếu field ' . $key);
        }
    }

    /**
     * @dataProvider endpointProvider
     *
     * @param string[] $expectedKeys
     */
    public function testEndpointMapsBusinessErrorFromBody(AbstractRequest $request, string $path, array $expectedKeys): void
    {
        $transport = (new MockTransport())->respondJson(409, [
            'timestamp' => 't',
            'code' => 409,
            'message' => 'Đã tồn tại',
            'errorDetails' => [],
        ]);
        $client = new PaySwitchClient(new PaySwitchConfig(self::BASE), $transport);

        try {
            $client->send($request);
            self::fail('Phải ném BusinessException');
        } catch (BusinessException $e) {
            self::assertSame('409', $e->getErrorCode());
            self::assertSame(409, $e->getHttpStatus());
        }
    }

    public function testHealthEndpointsUseGet(): void
    {
        $transport = (new MockTransport())
            ->respond(200, '/api')
            ->respond(200, 'Health Check OK !!!')
            ->respond(200, '{"openapi":"3.0.1"}');
        $client = new PaySwitchClient(new PaySwitchConfig(self::BASE), $transport);

        self::assertSame('/api', $client->home()->data);
        self::assertSame('Health Check OK !!!', $client->healthCheck()->data);
        self::assertSame('{"openapi":"3.0.1"}', $client->apiDocs()->data);

        self::assertSame(self::BASE . '/', $transport->calls[0]['url']);
        self::assertSame(self::BASE . '/healthcheck', $transport->calls[1]['url']);
        self::assertSame(self::BASE . '/api-docs', $transport->calls[2]['url']);
        foreach ($transport->calls as $call) {
            self::assertSame('GET', $call['method']);
        }
    }
}
