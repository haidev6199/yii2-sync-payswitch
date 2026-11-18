<?php

namespace Haidev6199\PaySwitch\Request\Merchant;

use Haidev6199\PaySwitch\Request\AbstractRequest;

/**
 * POST /api/merchant/get-merchant
 */
final class GetMerchantRequest extends AbstractRequest
{
    private $merchantId;

    public function __construct(int $merchantId)
    {
        $this->merchantId = $merchantId;
    }

    public function path(): string
    {
        return '/api/merchant/get-merchant';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return ['merchantId' => $this->merchantId];
    }

    protected function rules(): array
    {
        return $this->merchantId > 0 ? [] : ['merchantId phải là số dương.'];
    }
}
