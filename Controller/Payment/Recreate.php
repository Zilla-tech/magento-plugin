<?php

namespace Zilla\Payments\Controller\Payment;

use Magento\Sales\Model\Order;

class Recreate extends AbstractZillaStandard {

    public function execute() {

        $order = $this->checkoutSession->getLastRealOrder();
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation("Payment failed or cancelled")->save();

        }

        $this->checkoutSession->restoreQuote();
        $this->_redirect('checkout', ['_fragment' => 'payment']);
    }

}
