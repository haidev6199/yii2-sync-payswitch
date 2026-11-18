<?php

namespace Haidev6199\PaySwitch\Request\Reader;

use Haidev6199\PaySwitch\Request\AbstractRequest;
use Haidev6199\PaySwitch\Request\CommonStatus;

/**
 * POST /api/reader/add-reader
 * Bắt buộc: serialNumber, status, readerType, merchantId.
 */
final class AddReaderRequest extends AbstractRequest
{
    private $serialNumber;
    private $status;
    private $readerType;
    private $merchantId;

    public function __construct(string $serialNumber, string $status, string $readerType, int $merchantId)
    {
        $this->serialNumber = $serialNumber;
        $this->status = $status;
        $this->readerType = $readerType;
        $this->merchantId = $merchantId;
    }

    public function path(): string
    {
        return '/api/reader/add-reader';
    }

    public function toArray(): array
    {
        return [
            'serialNumber' => $this->serialNumber,
            'status' => $this->status,
            'readerType' => $this->readerType,
            'merchantId' => $this->merchantId,
        ];
    }

    protected function rules(): array
    {
        $errors = self::requiredErrors([
            'serialNumber' => $this->serialNumber,
            'status' => $this->status,
            'readerType' => $this->readerType,
        ]);
        if ($this->merchantId <= 0) {
            $errors[] = 'merchantId phải là số dương.';
        }
        if (!self::isBlank($this->status) && !CommonStatus::isValid($this->status)) {
            $errors[] = 'status phải là ' . implode(' hoặc ', CommonStatus::values()) . '.';
        }

        return $errors;
    }
}
