<?php

namespace Haidev6199\PaySwitch\Request\MidTid;

use Haidev6199\PaySwitch\Request\AbstractRequest;

/**
 * POST /api/mid-tid/add-sys-tid
 * Bắt buộc: sysTid, sysMid, pin (6-8 chữ số), merchantId.
 * acquirerTid yêu cầu kèm acquirer. address tối đa 255 ký tự.
 */
final class AddSysTidRequest extends AbstractRequest
{
    private $sysTid;
    private $sysMid;
    private $pin;
    private $merchantId;
    private $address;
    private $acquirerTid;
    private $acquirerMid;
    private $acquirer;
    private $currency;

    public function __construct(
        string $sysTid,
        string $sysMid,
        string $pin,
        int $merchantId,
        ?string $address = null,
        ?string $acquirerTid = null,
        ?string $acquirerMid = null,
        ?string $acquirer = null,
        ?string $currency = null
    ) {
        $this->sysTid = $sysTid;
        $this->sysMid = $sysMid;
        $this->pin = $pin;
        $this->merchantId = $merchantId;
        $this->address = $address;
        $this->acquirerTid = $acquirerTid;
        $this->acquirerMid = $acquirerMid;
        $this->acquirer = $acquirer;
        $this->currency = $currency;
    }

    public function path(): string
    {
        return '/api/mid-tid/add-sys-tid';
    }

    public function toArray(): array
    {
        return self::filter([
            'sysTid' => $this->sysTid,
            'sysMid' => $this->sysMid,
            'pin' => $this->pin,
            'merchantId' => $this->merchantId,
            'address' => $this->address,
            'acquirerTid' => $this->acquirerTid,
            'acquirerMid' => $this->acquirerMid,
            'acquirer' => $this->acquirer,
            'currency' => $this->currency,
        ]);
    }

    protected function rules(): array
    {
        $errors = self::requiredErrors([
            'sysTid' => $this->sysTid,
            'sysMid' => $this->sysMid,
            'pin' => $this->pin,
        ]);

        if (!self::isBlank($this->pin) && !self::isDigits($this->pin, 6, 8)) {
            $errors[] = 'pin phải gồm 6 đến 8 chữ số.';
        }
        if ($this->merchantId <= 0) {
            $errors[] = 'merchantId phải là số dương.';
        }
        if (!self::isBlank($this->address) && $this->strlen((string) $this->address) > 255) {
            $errors[] = 'address tối đa 255 ký tự.';
        }
        if (!self::isBlank($this->acquirerTid) && self::isBlank($this->acquirer)) {
            $errors[] = 'acquirer là bắt buộc khi có acquirerTid.';
        }
        if (!self::isBlank($this->currency) && !preg_match('/^[A-Z]{3}$/', (string) $this->currency)) {
            $errors[] = 'currency phải theo định dạng ISO 4217 3 ký tự, ví dụ VND.';
        }

        return $errors;
    }

    private function strlen(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
