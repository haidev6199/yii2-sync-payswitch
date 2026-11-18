<?php

namespace Haidev6199\PaySwitch\Request\MidTid;

use Haidev6199\PaySwitch\Request\AbstractRequest;

/**
 * POST /api/mid-tid/change-sys-tid-pin
 * Bắt buộc: sysTid, newPin (6-8 chữ số).
 */
final class ChangeSysTidPinRequest extends AbstractRequest
{
    private $sysTid;
    private $newPin;

    public function __construct(string $sysTid, string $newPin)
    {
        $this->sysTid = $sysTid;
        $this->newPin = $newPin;
    }

    public function path(): string
    {
        return '/api/mid-tid/change-sys-tid-pin';
    }

    public function toArray(): array
    {
        return ['sysTid' => $this->sysTid, 'newPin' => $this->newPin];
    }

    protected function rules(): array
    {
        $errors = self::requiredErrors(['sysTid' => $this->sysTid, 'newPin' => $this->newPin]);
        if (!self::isBlank($this->newPin) && !self::isDigits($this->newPin, 6, 8)) {
            $errors[] = 'newPin phải gồm 6 đến 8 chữ số.';
        }

        return $errors;
    }
}
