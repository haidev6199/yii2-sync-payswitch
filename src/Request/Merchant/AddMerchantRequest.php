<?php

namespace Haidev6199\PaySwitch\Request\Merchant;

use Haidev6199\PaySwitch\Request\AbstractRequest;

/**
 * POST /api/merchant/add-merchant
 * Bắt buộc: username, password, merchantName, shortName, city, ward, address.
 */
final class AddMerchantRequest extends AbstractRequest
{
    private $username;
    private $password;
    private $merchantName;
    private $shortName;
    private $city;
    private $ward;
    private $address;
    private $email;
    private $phone;

    public function __construct(
        string $username,
        string $password,
        string $merchantName,
        string $shortName,
        string $city,
        string $ward,
        string $address,
        ?string $email = null,
        ?string $phone = null
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->merchantName = $merchantName;
        $this->shortName = $shortName;
        $this->city = $city;
        $this->ward = $ward;
        $this->address = $address;
        $this->email = $email;
        $this->phone = $phone;
    }

    public function path(): string
    {
        return '/api/merchant/add-merchant';
    }

    public function toArray(): array
    {
        return self::filter([
            'username' => $this->username,
            'password' => $this->password,
            'merchantName' => $this->merchantName,
            'shortName' => $this->shortName,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'ward' => $this->ward,
            'address' => $this->address,
        ]);
    }

    protected function rules(): array
    {
        $errors = self::requiredErrors([
            'username' => $this->username,
            'password' => $this->password,
            'merchantName' => $this->merchantName,
            'shortName' => $this->shortName,
            'city' => $this->city,
            'ward' => $this->ward,
            'address' => $this->address,
        ]);

        if (!self::isBlank($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'email không đúng định dạng.';
        }
        if (!self::isBlank($this->phone) && !self::isDigits((string) $this->phone, 10, 10)) {
            $errors[] = 'phone phải gồm đúng 10 chữ số.';
        }

        return $errors;
    }
}
