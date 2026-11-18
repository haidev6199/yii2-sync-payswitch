<?php

namespace Haidev6199\PaySwitch\Request\MidTid;

use Haidev6199\PaySwitch\Request\AbstractRequest;
use Haidev6199\PaySwitch\Request\CommonStatus;

/**
 * POST /api/mid-tid/update-sys-tid-status
 * Bắt buộc: sysTid, status (CommonStatus).
 */
final class UpdateSysTidStatusRequest extends AbstractRequest
{
    private $sysTid;
    private $status;

    public function __construct(string $sysTid, string $status)
    {
        $this->sysTid = $sysTid;
        $this->status = $status;
    }

    public function path(): string
    {
        return '/api/mid-tid/update-sys-tid-status';
    }

    public function toArray(): array
    {
        return ['sysTid' => $this->sysTid, 'status' => $this->status];
    }

    protected function rules(): array
    {
        $errors = self::requiredErrors(['sysTid' => $this->sysTid, 'status' => $this->status]);
        if (!self::isBlank($this->status) && !CommonStatus::isValid($this->status)) {
            $errors[] = 'status phải là ' . implode(' hoặc ', CommonStatus::values()) . '.';
        }

        return $errors;
    }
}
