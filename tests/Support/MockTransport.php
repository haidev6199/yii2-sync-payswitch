<?php

namespace Haidev6199\PaySwitch\Tests\Support;

use Haidev6199\PaySwitch\Contracts\HttpTransportInterface;

/**
 * Transport giả lập: trả về response đã xếp hàng, ghi lại các lần gọi. Không gọi mạng.
 */
final class MockTransport implements HttpTransportInterface
{
    /** @var array<int, array{status: int, body: string}|\Throwable> */
    private $queue = [];

    /** @var array<int, array{method: string, url: string, payload: array|null}> */
    public $calls = [];

    public function respond(int $status, string $body): self
    {
        $this->queue[] = ['status' => $status, 'body' => $body];

        return $this;
    }

    public function respondJson(int $status, array $data): self
    {
        return $this->respond($status, (string) json_encode($data));
    }

    public function fail(\Throwable $error): self
    {
        $this->queue[] = $error;

        return $this;
    }

    public function get(string $url): array
    {
        return $this->next('GET', $url, null);
    }

    public function post(string $url, array $payload): array
    {
        return $this->next('POST', $url, $payload);
    }

    /**
     * @param array|null $payload
     *
     * @return array{status: int, body: string}
     */
    private function next(string $method, string $url, ?array $payload): array
    {
        $this->calls[] = ['method' => $method, 'url' => $url, 'payload' => $payload];

        if ($this->queue === []) {
            throw new \LogicException('MockTransport: không còn response nào được xếp hàng.');
        }

        $item = array_shift($this->queue);
        if ($item instanceof \Throwable) {
            throw $item;
        }

        return $item;
    }
}
