<?php

namespace Haidev6199\PaySwitch\Tests\Unit;

use Haidev6199\PaySwitch\Client\PaySwitchClient;
use Haidev6199\PaySwitch\Config\PaySwitchConfig;
use Haidev6199\PaySwitch\Exceptions\BusinessException;
use Haidev6199\PaySwitch\Exceptions\ConnectionException;
use Haidev6199\PaySwitch\Exceptions\HttpException;
use Haidev6199\PaySwitch\Exceptions\InvalidResponseException;
use Haidev6199\PaySwitch\Exceptions\TimeoutException;
use Haidev6199\PaySwitch\Exceptions\ValidationException;
use Haidev6199\PaySwitch\Request\Merchant\GetMerchantRequest;
use Haidev6199\PaySwitch\Request\Merchant\AddAcquirerRequest;
use Haidev6199\PaySwitch\Tests\Support\MockTransport;
use PHPUnit\Framework\TestCase;

final class PaySwitchClientTest extends TestCase
{
    private const BASE = 'http://payswitch.test';

    private function client(MockTransport $transport, bool $retry = true): PaySwitchClient
    {
        $config = new PaySwitchConfig(self::BASE, 30, 5, true, $retry);

        return new PaySwitchClient($config, $transport);
    }

    public function testSuccessReturnsApiResponseWithFullBodyAsData(): void
    {
        $body = ['code' => '200', 'message' => 'Success', 'timestamp' => 't', 'data' => ['id' => 42]];
        $transport = (new MockTransport())->respondJson(200, $body);

        $response = $this->client($transport)->getMerchant(new GetMerchantRequest(42));

        self::assertTrue($response->success);
        self::assertSame(200, $response->http_status);
        self::assertNull($response->error_code);
        self::assertNull($response->message);
        self::assertSame($body, $response->data);
    }

    public function testBusinessErrorWithHttp200IsThrown(): void
    {
        $transport = (new MockTransport())->respondJson(200, ['code' => '01', 'message' => 'Sai dữ liệu']);

        try {
            $this->client($transport)->getMerchant(new GetMerchantRequest(1));
            self::fail('Phải ném BusinessException');
        } catch (BusinessException $e) {
            self::assertSame('01', $e->getErrorCode());
            self::assertSame('Sai dữ liệu', $e->getMessage());
            self::assertSame(200, $e->getHttpStatus());
        }
    }

    public function testHttp404WithErrorEnvelopeIsBusinessException(): void
    {
        $transport = (new MockTransport())->respondJson(404, [
            'timestamp' => 't',
            'code' => 404,
            'message' => 'Merchant không tồn tại',
            'errorDetails' => [],
        ]);

        try {
            $this->client($transport)->getMerchant(new GetMerchantRequest(999999999));
            self::fail('Phải ném BusinessException');
        } catch (BusinessException $e) {
            self::assertSame('404', $e->getErrorCode());
            self::assertSame(404, $e->getHttpStatus());
        }
    }

    public function testHttp500WithoutEnvelopeIsHttpException(): void
    {
        $transport = (new MockTransport())->respond(500, '<html>Internal Server Error</html>');

        try {
            $this->client($transport)->addAcquirer(new AddAcquirerRequest('ACQ1'));
            self::fail('Phải ném HttpException');
        } catch (HttpException $e) {
            self::assertSame(500, $e->getHttpStatus());
            self::assertStringNotContainsString('Internal', $e->getMessage());
        }
    }

    public function testHttp400SpringDefaultBodyIsHttpException(): void
    {
        $transport = (new MockTransport())->respondJson(400, [
            'timestamp' => 't',
            'status' => 400,
            'error' => 'Bad Request',
            'path' => '/api/merchant/add-merchant',
        ]);

        $this->expectException(HttpException::class);
        $this->client($transport)->addAcquirer(new AddAcquirerRequest('ACQ1'));
    }

    public function testInvalidJsonOnSuccessStatusIsInvalidResponse(): void
    {
        $transport = (new MockTransport())->respond(200, '{not json');

        $this->expectException(InvalidResponseException::class);
        $this->client($transport)->getMerchant(new GetMerchantRequest(1));
    }

    public function testSuccessStatusWithoutCodeIsInvalidResponse(): void
    {
        $transport = (new MockTransport())->respondJson(200, ['data' => ['id' => 1]]);

        $this->expectException(InvalidResponseException::class);
        $this->client($transport)->getMerchant(new GetMerchantRequest(1));
    }

    public function testTimeoutIsPropagatedAfterRetryOnReadOperation(): void
    {
        // get-* được retry 1 lần; lần thứ 2 vẫn timeout thì exception được ném ra
        $transport = (new MockTransport())
            ->fail(new TimeoutException('timeout'))
            ->fail(new TimeoutException('timeout'));

        try {
            $this->client($transport)->getMerchant(new GetMerchantRequest(1));
            self::fail('Phải ném TimeoutException');
        } catch (TimeoutException $e) {
            self::assertCount(2, $transport->calls);
        }
    }

    public function testReadOperationIsRetriedOnceAfterConnectionError(): void
    {
        $transport = (new MockTransport())
            ->fail(new ConnectionException('down'))
            ->respondJson(200, ['code' => '200', 'message' => 'OK', 'data' => ['id' => 1]]);

        $response = $this->client($transport)->getMerchant(new GetMerchantRequest(1));

        self::assertTrue($response->success);
        self::assertCount(2, $transport->calls);
    }

    public function testReadOperationRetryCanBeDisabled(): void
    {
        $transport = (new MockTransport())->fail(new TimeoutException('timeout'));

        try {
            $this->client($transport, false)->getMerchant(new GetMerchantRequest(1));
            self::fail('Phải ném TimeoutException');
        } catch (TimeoutException $e) {
            self::assertCount(1, $transport->calls);
        }
    }

    public function testCreateOperationIsNeverRetried(): void
    {
        $transport = (new MockTransport())
            ->fail(new ConnectionException('down'))
            ->respondJson(200, ['code' => '200', 'message' => 'OK', 'data' => []]);

        try {
            $this->client($transport)->addAcquirer(new AddAcquirerRequest('ACQ1'));
            self::fail('Phải ném ConnectionException');
        } catch (ConnectionException $e) {
            self::assertCount(1, $transport->calls);
        }
    }

    public function testValidationErrorStopsBeforeTransport(): void
    {
        $transport = new MockTransport();

        try {
            $this->client($transport)->addAcquirer(new AddAcquirerRequest(''));
            self::fail('Phải ném ValidationException');
        } catch (ValidationException $e) {
            self::assertNotEmpty($e->getErrors());
            self::assertCount(0, $transport->calls);
        }
    }

    public function testHealthCheckReturnsRawBody(): void
    {
        $transport = (new MockTransport())->respond(200, 'Health Check OK !!!');

        $response = $this->client($transport)->healthCheck();

        self::assertTrue($response->success);
        self::assertSame('Health Check OK !!!', $response->data);
        self::assertSame(self::BASE . PaySwitchClient::PATH_HEALTHCHECK, $transport->calls[0]['url']);
        self::assertSame('GET', $transport->calls[0]['method']);
    }
}
