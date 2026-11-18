<?php

namespace Haidev6199\PaySwitch\Request\MidTid;

use Haidev6199\PaySwitch\Request\AbstractRequest;
use Haidev6199\PaySwitch\Request\CommonStatus;

/**
 * POST /api/mid-tid/add-acquirer-tid
 * Bắt buộc: sysTid, tid, mid, acquirer, status. currency (ISO 4217) nếu gửi thì phải hợp lệ.
 */
final class AddAcquirerTidRequest extends AbstractRequest
{
    private $sysTid;
    private $tid;
    private $mid;
    private $acquirer;
    private $status;
    private $currency;

    public function __construct(string $sysTid, string $tid, string $mid, string $acquirer, string $status, ?string $currency = null)
    {
        $this->sysTid = $sysTid;
        $this->tid = $tid;
        $this->mid = $mid;
        $this->acquirer = $acquirer;
        $this->status = $status;
        $this->currency = $currency;
    }

    public function path(): string
    {
        return '/api/mid-tid/add-acquirer-tid';
    }

    public function toArray(): array
    {
        return self::filter([
            'sysTid' => $this->sysTid,
            'tid' => $this->tid,
            'mid' => $this->mid,
            'acquirer' => $this->acquirer,
            'status' => $this->status,
            'currency' => $this->currency,
        ]);
    }

    protected function rules(): array
    {
        $errors = self::requiredErrors([
            'sysTid' => $this->sysTid,
            'tid' => $this->tid,
            'mid' => $this->mid,
            'acquirer' => $this->acquirer,
            'status' => $this->status,
        ]);

        if (!self::isBlank($this->status) && !CommonStatus::isValid($this->status)) {
            $errors[] = 'status phải là ' . implode(' hoặc ', CommonStatus::values()) . '.';
        }
        if (!self::isBlank($this->currency) && !preg_match('/^[A-Z]{3}$/', (string) $this->currency)) {
            $errors[] = 'currency phải theo định dạng ISO 4217 3 ký tự, ví dụ VND.';
        }

        return $errors;
    }
}
