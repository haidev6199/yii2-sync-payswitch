<?php

namespace Haidev6199\PaySwitch\Client\Transport;

use Haidev6199\PaySwitch\Config\PaySwitchConfig;
use Haidev6199\PaySwitch\Contracts\HttpTransportInterface;
use Haidev6199\PaySwitch\Exceptions\ConnectionException;
use Haidev6199\PaySwitch\Exceptions\TimeoutException;
use yii\httpclient\Client;
use yii\httpclient\Exception as HttpClientException;
use yii\httpclient\Request;

/**
 * HTTP transport dùng yii\httpclient. Áp dụng timeout, connect timeout và kiểm tra SSL theo cấu hình.
 * Thông báo lỗi không chứa URL, header hay payload.
 */
final class Yii2HttpTransport implements HttpTransportInterface
{
    /** @var PaySwitchConfig */
    private $config;
    /** @var Client */
    private $client;

    public function __construct(PaySwitchConfig $config, ?Client $client = null)
    {
        $this->config = $config;
        $this->client = $client ?: new Client();
    }

    public function get(string $url): array
    {
        return $this->send($this->newRequest('GET', $url));
    }

    public function post(string $url, array $payload): array
    {
        $request = $this->newRequest('POST', $url)
            ->setFormat(Client::FORMAT_JSON)
            ->setData($payload);

        return $this->send($request);
    }

    private function newRequest(string $method, string $url): Request
    {
        $verify = $this->config->isSslVerify();

        return $this->client->createRequest()
            ->setMethod($method)
            ->setUrl($url)
            ->setHeaders(['Accept' => 'application/json'])
            ->setOptions([
                CURLOPT_TIMEOUT => $this->config->getTimeout(),
                CURLOPT_CONNECTTIMEOUT => $this->config->getConnectTimeout(),
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
            ]);
    }

    /**
     * @return array{status: int, body: string}
     */
    private function send(Request $request): array
    {
        try {
            $response = $request->send();
        } catch (HttpClientException $e) {
            throw $this->mapError($e);
        }

        return [
            'status' => (int) $response->getStatusCode(),
            'body' => (string) $response->getContent(),
        ];
    }

    private function mapError(HttpClientException $e): \RuntimeException
    {
        $message = strtolower($e->getMessage());
        if (strpos($message, 'timed out') !== false || strpos($message, 'timeout') !== false) {
            return new TimeoutException('Hết thời gian chờ phản hồi từ PaySwitch.', 0, $e);
        }

        return new ConnectionException('Không thể kết nối tới PaySwitch.', 0, $e);
    }
}
