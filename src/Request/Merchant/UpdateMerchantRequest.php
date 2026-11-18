<?php

namespace Haidev6199\PaySwitch\Request\Merchant;

use Haidev6199\PaySwitch\Request\AbstractRequest;

/**
 * POST /api/merchant/update-merchant
 * Bắt buộc: merchantId, merchantName.
 */
final class UpdateMerchantRequest extends AbstractRequest
{
    private $merchantId;
    private $merchantName;
    private $email;
    private $phone;
    private $city;
    private $ward;
    private $address;

    public function __construct(
        int $merchantId,
        string $merchantName,
        ?string $email = null,
        ?string $phone = null,
        ?string $city = null,
        ?string $ward = null,
        ?string $address = null
    ) {
        $this->merchantId = $merchantId;
        $this->merchantName = $merchantName;
        $this->email = $email;
        $this->phone = $phone;
        $this->city = $city;
        $this->ward = $ward;
        $this->address = $address;
    }

    public function path(): string
    {
        return '/api/merchant/update-merchant';
    }

    public function toArray(): array
    {
        return self::filter([
            'merchantId' => $this->merchantId,
            'merchantName' => $this->merchantName,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'ward' => $this->ward,
            'address' => $this->address,
        ]);
    }

    protected function rules(): array
    {
        $errors = [];
        if ($this->merchantId <= 0) {
            $errors[] = 'merchantId phải là số dương.';
        }
        $errors = array_merge($errors, self::requiredErrors(['merchantName' => $this->merchantName]));

        if (!self::isBlank($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'email không đúng định dạng.';
        }
        if (!self::isBlank($this->phone) && !self::isDigits((string) $this->phone, 10, 10)) {
            $errors[] = 'phone phải gồm đúng 10 chữ số.';
        }

        return $errors;
    }
}
