<?php

namespace Haidev6199\PaySwitch\Client;

use Haidev6199\PaySwitch\Client\Transport\Yii2HttpTransport;
use Haidev6199\PaySwitch\Config\PaySwitchConfig;
use Haidev6199\PaySwitch\Contracts\HttpTransportInterface;
use Haidev6199\PaySwitch\Exceptions\BusinessException;
use Haidev6199\PaySwitch\Exceptions\ConnectionException;
use Haidev6199\PaySwitch\Exceptions\HttpException;
use Haidev6199\PaySwitch\Exceptions\InvalidResponseException;
use Haidev6199\PaySwitch\Exceptions\TimeoutException;
use Haidev6199\PaySwitch\Exceptions\ValidationException;
use Haidev6199\PaySwitch\Request\AbstractRequest;
use Haidev6199\PaySwitch\Request\MidTid\AddAcquirerTidRequest;
use Haidev6199\PaySwitch\Request\MidTid\AddSysTidRequest;
use Haidev6199\PaySwitch\Request\MidTid\ChangeSysTidPinRequest;
use Haidev6199\PaySwitch\Request\MidTid\GetAcquirerTidRequest;
use Haidev6199\PaySwitch\Request\MidTid\GetSysTidRequest;
use Haidev6199\PaySwitch\Request\MidTid\UpdateSysTidStatusRequest;
use Haidev6199\PaySwitch\Request\Merchant\AddAcquirerRequest;
use Haidev6199\PaySwitch\Request\Merchant\AddMerchantRequest;
use Haidev6199\PaySwitch\Request\Merchant\GetMerchantRequest;
use Haidev6199\PaySwitch\Request\Merchant\UpdateMerchantRequest;
use Haidev6199\PaySwitch\Request\Reader\AddReaderRequest;
use Haidev6199\PaySwitch\Request\Reader\GetReaderRequest;
use Haidev6199\PaySwitch\Request\Reader\ResetConfigFlagsRequest;
use Haidev6199\PaySwitch\Response\ApiResponse;

/**
 * Client nghiệp vụ PaySwitch. Mọi method nhận Request DTO và trả ApiResponse hoặc ném exception.
 *
 * Quy tắc thành công: HTTP 2xx và body có code = "200".
 * Retry: chỉ endpoint chỉ đọc (get-*) và chỉ khi lỗi kết nối/timeout, tối đa 1 lần.
 */
final class PaySwitchClient
{
    public const SUCCESS_CODE = '200';
    public const PATH_HOME = '/';
    public const PATH_HEALTHCHECK = '/healthcheck';
    public const PATH_API_DOCS = '/api-docs';

    /** @var PaySwitchConfig */
    private $config;
    /** @var HttpTransportInterface */
    private $transport;

    public function __construct(PaySwitchConfig $config, ?HttpTransportInterface $transport = null)
    {
        $this->config = $config;
        $this->transport = $transport ?: new Yii2HttpTransport($config);
    }

    /**
     * @throws ValidationException|BusinessException|HttpException|InvalidResponseException|ConnectionException|TimeoutException
     */
    public function send(AbstractRequest $request): ApiResponse
    {
        $errors = $request->validate();
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $url = $this->config->url($request->path());
        $payload = $request->toArray();

        $raw = $this->withRetry(
            function () use ($url, $payload): array {
                return $this->transport->post($url, $payload);
            },
            $request->isReadOnly()
        );

        return $this->parseJsonResponse($raw['status'], $raw['body']);
    }

    // ---- Merchant ----

    public function addMerchant(AddMerchantRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    public function getMerchant(GetMerchantRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    public function updateMerchant(UpdateMerchantRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    public function addAcquirer(AddAcquirerRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    // ---- Reader ----

    public function addReader(AddReaderRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    public function getReader(GetReaderRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    public function resetConfigFlags(ResetConfigFlagsRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    // ---- MID / TID ----

    public function addSysTid(AddSysTidRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    public function addAcquirerTid(AddAcquirerTidRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    public function getAcquirerTid(GetAcquirerTidRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    public function getSysTid(GetSysTidRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    public function changeSysTidPin(ChangeSysTidPinRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    public function updateSysTidStatus(UpdateSysTidStatusRequest $request): ApiResponse
    {
        return $this->send($request);
    }

    // ---- Health (không dùng JSON envelope) ----

    /**
     * GET /, data = chuỗi trả về.
     */
    public function home(): ApiResponse
    {
        return $this->health(self::PATH_HOME);
    }

    /**
     * GET /healthcheck, data = chuỗi trả về.
     */
    public function healthCheck(): ApiResponse
    {
        return $this->health(self::PATH_HEALTHCHECK);
    }

    /**
     * GET /api-docs (chỉ bật ở profile local/dev phía server).
     */
    public function apiDocs(): ApiResponse
    {
        return $this->health(self::PATH_API_DOCS);
    }

    private function health(string $path): ApiResponse
    {
        $url = $this->config->url($path);
        $raw = $this->withRetry(
            function () use ($url): array {
                return $this->transport->get($url);
            },
            true
        );

        if (!self::isSuccessStatus($raw['status'])) {
            throw new HttpException(sprintf('PaySwitch trả về HTTP %d.', $raw['status']), $raw['status'], $raw['body']);
        }

        return new ApiResponse(true, $raw['status'], null, null, $raw['body']);
    }

    /**
     * @param callable $operation trả về ['status' => int, 'body' => string]
     *
     * @throws ConnectionException|TimeoutException
     */
    private function withRetry(callable $operation, bool $isReadOnly): array
    {
        $maxAttempts = ($isReadOnly && $this->config->isRetryReadOperations()) ? 2 : 1;
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                return $operation();
            } catch (ConnectionException | TimeoutException $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @throws BusinessException|HttpException|InvalidResponseException
     */
    private function parseJsonResponse(int $status, string $body): ApiResponse
    {
        $decoded = json_decode($body, true);
        $isSuccess = self::isSuccessStatus($status);

        if (!is_array($decoded)) {
            if ($isSuccess) {
                throw new InvalidResponseException('Phản hồi PaySwitch không phải JSON hợp lệ.', $status, $body);
            }

            throw new HttpException(sprintf('PaySwitch trả về HTTP %d.', $status), $status, $body);
        }

        if (!array_key_exists('code', $decoded) || !array_key_exists('message', $decoded)) {
            if ($isSuccess) {
                throw new InvalidResponseException('Phản hồi PaySwitch thiếu code hoặc message.', $status, $body);
            }

            throw new HttpException(sprintf('PaySwitch trả về HTTP %d.', $status), $status, $body);
        }

        $code = (string) $decoded['code'];
        $message = (string) $decoded['message'];

        if ($isSuccess && $code === self::SUCCESS_CODE) {
            return new ApiResponse(true, $status, null, null, $decoded);
        }

        throw new BusinessException(
            $message !== '' ? $message : 'PaySwitch trả về lỗi nghiệp vụ.',
            $code,
            $status,
            $body
        );
    }

    private static function isSuccessStatus(int $status): bool
    {
        return $status >= 200 && $status < 300;
    }
}
