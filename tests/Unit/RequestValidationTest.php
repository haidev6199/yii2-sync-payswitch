<?php

namespace Haidev6199\PaySwitch\Tests\Unit;

use Haidev6199\PaySwitch\Request\Merchant\AddAcquirerRequest;
use Haidev6199\PaySwitch\Request\Merchant\AddMerchantRequest;
use Haidev6199\PaySwitch\Request\Merchant\UpdateMerchantRequest;
use Haidev6199\PaySwitch\Request\MidTid\AddAcquirerTidRequest;
use Haidev6199\PaySwitch\Request\MidTid\AddSysTidRequest;
use Haidev6199\PaySwitch\Request\MidTid\ChangeSysTidPinRequest;
use Haidev6199\PaySwitch\Request\MidTid\UpdateSysTidStatusRequest;
use Haidev6199\PaySwitch\Request\Reader\ResetConfigFlagsRequest;
use PHPUnit\Framework\TestCase;

final class RequestValidationTest extends TestCase
{
    private function addMerchant(array $override = []): AddMerchantRequest
    {
        $d = array_merge([
            'username' => 'merchant01',
            'password' => 'Passw0rd!',
            'merchantName' => 'Cua Hang ABC',
            'shortName' => 'ABC',
            'city' => 'Ho Chi Minh',
            'ward' => 'Phuong Ben Nghe',
            'address' => '123 Nguyen Hue',
            'email' => 'merchant01@example.com',
            'phone' => '0912345678',
        ], $override);

        return new AddMerchantRequest(
            $d['username'], $d['password'], $d['merchantName'], $d['shortName'],
            $d['city'], $d['ward'], $d['address'], $d['email'], $d['phone']
        );
    }

    public function testValidAddMerchantPasses(): void
    {
        self::assertSame([], $this->addMerchant()->validate());
    }

    public function testAddMerchantRequiresCityWardAddress(): void
    {
        self::assertNotEmpty($this->addMerchant(['city' => ''])->validate());
        self::assertNotEmpty($this->addMerchant(['ward' => ' '])->validate());
        self::assertNotEmpty($this->addMerchant(['address' => ''])->validate());
    }

    public function testAddMerchantPhoneMustBeTenDigits(): void
    {
        self::assertNotEmpty($this->addMerchant(['phone' => '0912'])->validate());
    }

    public function testAddMerchantOmitsOptionalNullFields(): void
    {
        $request = new AddMerchantRequest('u', 'p', 'n', 's', 'c', 'w', 'a');
        $payload = $request->toArray();

        self::assertArrayNotHasKey('email', $payload);
        self::assertArrayNotHasKey('phone', $payload);
    }

    public function testUpdateMerchantRejectsInvalidEmail(): void
    {
        $request = new UpdateMerchantRequest(1, 'Name', 'not-an-email');
        self::assertNotEmpty($request->validate());
    }

    public function testAddAcquirerRejectsUnknownStatus(): void
    {
        self::assertNotEmpty((new AddAcquirerRequest('ACQ1', null, 'UNKNOWN'))->validate());
        self::assertSame([], (new AddAcquirerRequest('ACQ1', null, 'ACTIVE'))->validate());
    }

    public function testSysTidPinAcceptsSixToEightDigits(): void
    {
        $make = function (string $pin): AddSysTidRequest {
            return new AddSysTidRequest('ST1', 'SM1', $pin, 1);
        };

        self::assertNotEmpty($make('12345')->validate());
        self::assertSame([], $make('123456')->validate());
        self::assertSame([], $make('12345678')->validate());
        self::assertNotEmpty($make('123456789')->validate());
        self::assertNotEmpty($make('1234a6')->validate());
    }

    public function testAcquirerTidRequiresAcquirer(): void
    {
        $request = new AddSysTidRequest('ST1', 'SM1', '123456', 1, null, 'T1', 'M1', null, null);
        self::assertNotEmpty($request->validate());
    }

    public function testAddSysTidRejectsInvalidCurrency(): void
    {
        $request = new AddSysTidRequest('ST1', 'SM1', '123456', 1, null, null, null, null, 'XXY1');
        self::assertNotEmpty($request->validate());
    }

    public function testAddSysTidAddressLimitIs255Characters(): void
    {
        $tooLong = str_repeat('a', 256);
        self::assertNotEmpty((new AddSysTidRequest('ST1', 'SM1', '123456', 1, $tooLong))->validate());
    }

    public function testAddAcquirerTidValidatesCurrencyAndStatus(): void
    {
        // Validation phía client chỉ kiểm tra định dạng [A-Z]{3}; mã ISO hợp lệ do server kiểm tra
        self::assertNotEmpty((new AddAcquirerTidRequest('ST1', 'T1', 'M1', 'ACQ', 'ACTIVE', 'usd'))->validate());
        self::assertNotEmpty((new AddAcquirerTidRequest('ST1', 'T1', 'M1', 'ACQ', 'BAD'))->validate());
        self::assertSame([], (new AddAcquirerTidRequest('ST1', 'T1', 'M1', 'ACQ', 'ACTIVE'))->validate());
    }

    public function testResetFlagsRequiresExactlyOneTarget(): void
    {
        self::assertNotEmpty((new ResetConfigFlagsRequest(null, false))->validate());
        self::assertNotEmpty((new ResetConfigFlagsRequest('SN1', true))->validate());
        self::assertSame([], (new ResetConfigFlagsRequest(null, true))->validate());
    }

    public function testResetFlagsAllReadersFalseWithSerialIsValidAndNotSent(): void
    {
        $request = new ResetConfigFlagsRequest('SN1', false);

        self::assertSame([], $request->validate());
        self::assertSame(['serialNumber' => 'SN1'], $request->toArray());
    }

    public function testResetFlagsEmptyConfigTypeIsOmitted(): void
    {
        $request = new ResetConfigFlagsRequest(null, true, '');

        self::assertSame(['allReaders' => true], $request->toArray());
    }

    public function testResetFlagsRejectsUnknownConfigType(): void
    {
        self::assertNotEmpty((new ResetConfigFlagsRequest('SN1', false, 'OTHER'))->validate());
    }

    public function testChangePinValidatesNewPin(): void
    {
        self::assertNotEmpty((new ChangeSysTidPinRequest('ST1', '12'))->validate());
        self::assertSame([], (new ChangeSysTidPinRequest('ST1', '654321'))->validate());
    }

    public function testUpdateSysTidStatusRejectsUnknownStatus(): void
    {
        self::assertNotEmpty((new UpdateSysTidStatusRequest('ST1', 'DELETED'))->validate());
    }
}
