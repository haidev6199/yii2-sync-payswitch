<?php

namespace Haidev6199\PaySwitch\Request\Merchant;

use Haidev6199\PaySwitch\Request\AbstractRequest;
use Haidev6199\PaySwitch\Request\CommonStatus;

/**
 * POST /api/merchant/add-acquirer
 * Bắt buộc: name. Tên acquirer là duy nhất (server trả 409 nếu trùng).
 */
final class AddAcquirerRequest extends AbstractRequest
{
    private $name;
    private $description;
    private $status;

    public function __construct(string $name, ?string $description = null, ?string $status = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->status = $status;
    }

    public function path(): string
    {
        return '/api/merchant/add-acquirer';
    }

    public function toArray(): array
    {
        return self::filter([
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ]);
    }

    protected function rules(): array
    {
        $errors = self::requiredErrors(['name' => $this->name]);
        if (!self::isBlank($this->status) && !CommonStatus::isValid((string) $this->status)) {
            $errors[] = 'status phải là ' . implode(' hoặc ', CommonStatus::values()) . '.';
        }

        return $errors;
    }
}
