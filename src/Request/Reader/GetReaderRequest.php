<?php

namespace Haidev6199\PaySwitch\Request\Reader;

use Haidev6199\PaySwitch\Request\AbstractRequest;

/**
 * POST /api/reader/get-reader
 */
final class GetReaderRequest extends AbstractRequest
{
    private $serialNumber;

    public function __construct(string $serialNumber)
    {
        $this->serialNumber = $serialNumber;
    }

    public function path(): string
    {
        return '/api/reader/get-reader';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return ['serialNumber' => $this->serialNumber];
    }

    protected function rules(): array
    {
        return self::requiredErrors(['serialNumber' => $this->serialNumber]);
    }
}
