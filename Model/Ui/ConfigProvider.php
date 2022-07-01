<?php

namespace Zilla\Payments\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\Store as Store;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    protected $method;

    public function __construct(PaymentHelper $paymentHelper, Store $store)
    {
        $this->method = $paymentHelper->getMethodInstance(\Zilla\Payments\Model\Payment\Zilla::CODE);
        $this->store = $store;
    }


    public function getStore()
    {
        return $this->store;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $public_key = $this->method->getConfigData('live_public_key');
        $secret_key = $this->method->getConfigData('live_secret_key');

        if ($this->method->getConfigData('test_mode')) {
            $public_key = $this->method->getConfigData('test_public_key');
            $secret_key = $this->method->getConfigData('test_secret_key');
        }

        return [
            'payment' => [
                \Zilla\Payments\Model\Payment\Zilla::CODE => [
                    'merchant_id' => $this->method->getConfigData('merchant_id'),
                    'public_key' => $public_key,
                    'secret_key' => $secret_key,
                    'api_url' => $this->store->getBaseUrl() . 'rest/',
                    'recreate_quote_url' => $this->store->getBaseUrl() . 'zilla/payment/recreate',
                ]
            ]
        ];
    }

    /**
     * Get secret key for webhook process
     *
     * @return array
     */
    public function getSecretKeyArray()
    {
        $data = ["live" => $this->method->getConfigData('live_secret_key')];
        if ($this->method->getConfigData('test_mode')) {
            $data = ["test" => $this->method->getConfigData('test_secret_key')];
        }

        return $data;
    }

    public function getPublicKey()
    {
        $publicKey = $this->method->getConfigData('live_public_key');
        if ($this->method->getConfigData('test_mode')) {
            $publicKey = $this->method->getConfigData('test_public_key');
        }
        return $publicKey;
    }
}
