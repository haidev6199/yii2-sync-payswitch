<?php

namespace Haidev6199\PaySwitch\Contracts;

use Haidev6199\PaySwitch\Exceptions\ConnectionException;
use Haidev6199\PaySwitch\Exceptions\TimeoutException;

/**
 * Lớp trừu tượng HTTP transport. Trả về mảng ['status' => int, 'body' => string].
 * Không trả exception HTTP 4xx/5xx; chỉ ném ConnectionException/TimeoutException khi lỗi mạng.
 */
interface HttpTransportInterface
{
    /**
     * @throws ConnectionException
     * @throws TimeoutException
     *
     * @return array{status: int, body: string}
     */
    public function get(string $url): array;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws ConnectionException
     * @throws TimeoutException
     *
     * @return array{status: int, body: string}
     */
    public function post(string $url, array $payload): array;
}
