<?php

namespace Haidev6199\PaySwitch\Exceptions;

/**
 * Dữ liệu không hợp lệ, ném trước khi gửi request. Các thông báo lỗi không chứa giá trị đầu vào.
 */
class ValidationException extends PaySwitchException
{
    /** @var string[] */
    private $errors;

    /**
     * @param string[] $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Dữ liệu không hợp lệ: ' . implode(' ', $errors));
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
