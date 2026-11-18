<?php

namespace Haidev6199\PaySwitch\Exceptions;

/**
 * HTTP 4xx/5xx mà body không có cấu trúc lỗi nghiệp vụ (code + message).
 * rawBody chỉ phục vụ debug nội bộ, không đưa vào message và không log.
 */
class HttpException extends PaySwitchException
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
