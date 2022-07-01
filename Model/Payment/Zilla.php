<?php

namespace Zilla\Payments\Model\Payment;

class Zilla extends \Magento\Payment\Model\Method\AbstractMethod
{

    const CODE = 'zilla_payments';

    protected $_code = self::CODE;
    protected $_isOffline = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }
}
