<?php

namespace Haidev6199\PaySwitch\Request;

/**
 * Base cho mọi request DTO. Mỗi request là một endpoint trong Postman collection.
 */
abstract class AbstractRequest
{
    /** Đường dẫn endpoint, ví dụ /api/merchant/add-merchant */
    abstract public function path(): string;

    /** Payload gửi lên PaySwitch (chỉ gọi sau khi validate() rỗng). */
    abstract public function toArray(): array;

    /**
     * @return string[] Danh sách lỗi; mảng rỗng nghĩa là hợp lệ
     */
    abstract protected function rules(): array;

    /**
     * Endpoint chỉ đọc (get-*) có thể retry khi lỗi kết nối.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * @return string[]
     */
    public function validate(): array
    {
        return $this->rules();
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return string[]
     */
    protected static function requiredErrors(array $fields): array
    {
        $errors = [];
        foreach ($fields as $name => $value) {
            if (self::isBlank($value)) {
                $errors[] = $name . ' là bắt buộc.';
            }
        }

        return $errors;
    }

    /**
     * @param mixed $value
     */
    protected static function isBlank($value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    protected static function isDigits(string $value, int $min, int $max): bool
    {
        return preg_match('/^\d{' . $min . ',' . $max . '}$/', $value) === 1;
    }

    /**
     * Loại bỏ các field null hoặc chuỗi rỗng, giữ nguyên false/0.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected static function filter(array $data): array
    {
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
