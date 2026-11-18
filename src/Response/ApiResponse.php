<?php

namespace Haidev6199\PaySwitch\Response;

/**
 * Envelope thống nhất trả về cho caller.
 *
 * - success: true khi HTTP 2xx và code = "200".
 * - http_status: HTTP status thực tế.
 * - error_code, message: null khi thành công.
 * - data: toàn bộ JSON body PaySwitch trả về (gồm code, message, timestamp, data).
 *
 * Lỗi nghiệp vụ không trả ApiResponse mà ném BusinessException.
 */
final class ApiResponse
{
    /** @var bool */
    public $success;
    /** @var int */
    public $http_status;
    /** @var string|null */
    public $error_code;
    /** @var string|null */
    public $message;
    /** @var mixed */
    public $data;

    /**
     * @param mixed $data
     */
    public function __construct(bool $success, int $httpStatus, ?string $errorCode, ?string $message, $data)
    {
        $this->success = $success;
        $this->http_status = $httpStatus;
        $this->error_code = $errorCode;
        $this->message = $message;
        $this->data = $data;
    }
}
