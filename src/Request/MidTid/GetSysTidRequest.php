<?php

namespace Haidev6199\PaySwitch\Request\MidTid;

use Haidev6199\PaySwitch\Request\AbstractRequest;

/**
 * POST /api/mid-tid/get-sys-tid
 */
final class GetSysTidRequest extends AbstractRequest
{
    private $sysTid;

    public function __construct(string $sysTid)
    {
        $this->sysTid = $sysTid;
    }

    public function path(): string
    {
        return '/api/mid-tid/get-sys-tid';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return ['sysTid' => $this->sysTid];
    }

    protected function rules(): array
    {
        return self::requiredErrors(['sysTid' => $this->sysTid]);
    }
}
