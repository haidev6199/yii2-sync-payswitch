<?php

namespace Haidev6199\PaySwitch\Request\MidTid;

use Haidev6199\PaySwitch\Request\AbstractRequest;

/**
 * POST /api/mid-tid/get-acquirer-tid
 */
final class GetAcquirerTidRequest extends AbstractRequest
{
    private $tid;
    private $acquirer;

    public function __construct(string $tid, string $acquirer)
    {
        $this->tid = $tid;
        $this->acquirer = $acquirer;
    }

    public function path(): string
    {
        return '/api/mid-tid/get-acquirer-tid';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return ['tid' => $this->tid, 'acquirer' => $this->acquirer];
    }

    protected function rules(): array
    {
        return self::requiredErrors(['tid' => $this->tid, 'acquirer' => $this->acquirer]);
    }
}
