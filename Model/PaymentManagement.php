<?php

namespace Zilla\Payments\Model;

use Exception;
use Magento\Payment\Helper\Data as PaymentHelper;
use Zilla\Payments\Model\Payment\Zilla as ZillaModel;

class PaymentManagement implements \Zilla\Payments\Api\PaymentManagementInterface
{

    protected $zillaPaymentInstance;

    protected $orderInterface;
    protected $checkoutSession;
    protected $secretKey;
    protected $baseApiUrl;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    private $eventManager;

    public function __construct(
        PaymentHelper $paymentHelper,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Checkout\Model\Session $checkoutSession

    ) {
        $this->eventManager = $eventManager;
        $this->zillaPaymentInstance = $paymentHelper->getMethodInstance(ZillaModel::CODE);

        $this->orderInterface = $orderInterface;
        $this->checkoutSession = $checkoutSession;

        $this->secretKey = $this->zillaPaymentInstance->getConfigData('live_secret_key');
        $this->publicKey = $this->zillaPaymentInstance->getConfigData('live_public_key');
        $this->baseApiUrl = 'https://bnpl-gateway.usezilla.com';
        if ($this->zillaPaymentInstance->getConfigData('test_mode')) {
            $this->baseApiUrl = 'https://bnpl-gateway-sandbox.zilla.africa';
            $this->secretKey = $this->zillaPaymentInstance->getConfigData('test_secret_key');
            $this->publicKey = $this->zillaPaymentInstance->getConfigData('test_public_key');
        }
    }

    /**
     * @param string $reference
     * @return bool
     */
    public function verifyPayment($reference)
    {
        // we are appending quoteid
        $ref = explode('_-~-_', $reference);
        $reference = $ref[0];
        $quoteId = $ref[1];

        try {
            $jwt = $this->getToken();

            $transaction_details = $this->verifyOrder($reference, $jwt);

            $order = $this->getOrder();

            if ($order && $order->getQuoteId() === $quoteId) {

                // dispatch the `zilla_payments_verify_after` event to update the order status
                $this->eventManager->dispatch('zilla_payments_verify_after', [
                    "zilla_order" => $order,
                    "zilla_detail" => $transaction_details,
                ]);

                return json_encode([
                    'success' => true,
                    'data' => $transaction_details
                ]);
            }
        } catch (Exception $e) {
            return json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
        return json_encode([
            'status' => false,
            'message' => "quoteId doesn't match transaction"
        ]);
    }

    /**
     * Loads the order based on the last real order
     * @return boolean
     */
    private function getOrder()
    {
        // get the last real order id
        $lastOrder = $this->checkoutSession->getLastRealOrder();
        if ($lastOrder) {
            $lastOrderId = $lastOrder->getIncrementId();
        } else {
            return false;
        }

        if ($lastOrderId) {
            // load and return the order instance
            return $this->orderInterface->loadByIncrementId($lastOrderId);
        }
        return false;
    }

    private function verifyOrder($ref, $jwt)
    {

        // Create a new cURL resource
        $ch = curl_init($this->baseApiUrl . '/bnpl/purchase-order/' . $ref . '/merchant-full-info');


        $headers = array(
            "Authorization: Bearer " . $jwt,
            "Content-Type: application/json",
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the POST request
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Close cURL resource
        curl_close($ch);

        if ($httpcode !== 200) {
            throw new \Exception("Unable to authenticated key");
        }

        $data = json_decode($result, true);

        return $data['data'];
    }

    private function getToken()
    {
        // Create a new cURL resource
        $ch = curl_init($this->baseApiUrl . '/bnpl/auth/sa');

        // Setup request to send json via POST
        $payload = json_encode(array(
            "publicKey" => $this->publicKey,
            "secretKey" => $this->secretKey
        ));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the POST request
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Close cURL resource
        curl_close($ch);

        if ($httpcode !== 200) {
            throw new \Exception("Unable to authenticated key");
        }

        $data = json_decode($result, true);

        return $data['data']['token'];
    }
}
