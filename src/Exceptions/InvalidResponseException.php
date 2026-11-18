<?php

namespace Haidev6199\PaySwitch\Exceptions;

/**
 * Response thành công về HTTP nhưng không đúng cấu trúc (không phải JSON, thiếu code/message).
 */
class InvalidResponseException extends PaySwitchException
{
    /** @var int */
    private $httpStatus;
    /** @var string|null */
    private $rawBody;

    public function __construct(string $message, int $httpStatus, ?string $rawBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $httpStatus, $previous);
        $this->httpStatus = $httpStatus;
        $this->rawBody = $rawBody;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getRawBody(): ?string
    {
        return $this->rawBody;
    }
}
