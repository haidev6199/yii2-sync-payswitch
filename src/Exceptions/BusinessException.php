<?php

namespace Haidev6199\PaySwitch\Exceptions;

/**
 * Lỗi nghiệp vụ PaySwitch: body có code khác "200" (kể cả khi HTTP 200), hoặc HTTP 4xx/5xx có code + message.
 */
class BusinessException extends PaySwitchException
{
    /** @var string */
    private $errorCode;
    /** @var int */
    private $httpStatus;
    /** @var string|null */
    private $rawBody;

    public function __construct(string $message, string $errorCode, int $httpStatus, ?string $rawBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
        $this->rawBody = $rawBody;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
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
