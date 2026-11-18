<?php

namespace Haidev6199\PaySwitch\Components;

use Haidev6199\PaySwitch\Client\PaySwitchClient;
use Haidev6199\PaySwitch\Config\PaySwitchConfig;
use Haidev6199\PaySwitch\Contracts\HttpTransportInterface;
use yii\base\Component;

/**
 * Yii2 component. Cấu hình trong config/web.php; mọi thuộc tính để null sẽ đọc từ biến môi trường PAYSWITCH_*.
 *
 *   'payswitch' => [
 *       'class' => \Haidev6199\PaySwitch\Components\PaySwitchComponent::class,
 *   ],
 *
 * Gọi: Yii::$app->payswitch->addMerchant(new AddMerchantRequest(...));
 */
class PaySwitchComponent extends Component
{
    /** @var string|null */
    public $baseUrl;
    /** @var int|null */
    public $timeout;
    /** @var int|null */
    public $connectTimeout;
    /** @var bool|null */
    public $sslVerify;
    /** @var bool|null */
    public $retryReadOperations;
    /** @var HttpTransportInterface|null Transport tùy chỉnh (ví dụ để test). */
    public $transport;

    /** @var PaySwitchClient|null */
    private $client;

    public function getClient(): PaySwitchClient
    {
        if ($this->client === null) {
            $config = PaySwitchConfig::fromEnv([
                'baseUrl' => $this->baseUrl,
                'timeout' => $this->timeout,
                'connectTimeout' => $this->connectTimeout,
                'sslVerify' => $this->sslVerify,
                'retryReadOperations' => $this->retryReadOperations,
            ]);
            $this->client = new PaySwitchClient($config, $this->transport);
        }

        return $this->client;
    }

    /**
     * Chuyển tiếp các method nghiệp vụ của PaySwitchClient. Method khác được xử lý bởi Yii Component (behaviors).
     *
     * @param string $name
     * @param array<mixed> $params
     *
     * @return mixed
     */
    public function __call($name, $params)
    {
        if (method_exists(PaySwitchClient::class, $name)) {
            return call_user_func_array([$this->getClient(), $name], $params);
        }

        return parent::__call($name, $params);
    }
}
