<?php

namespace Dagcoin\PaymentGateway\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class ControllerCancelOrder implements ObserverInterface
{
    public $checkoutSession;
    public $orderFactory;
    private $helper;

    public function __construct(
        Session $checkoutSession,
        OrderFactory $orderFactory,
        \Dagcoin\PaymentGateway\Model\DagpayHelper $helper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getData('order');

        if (!$order) {
            return;
        }

        $transactions = $this->helper->getTransactionsByOrderId($order->getId());
        if (count($transactions) === 0) {
            return;
        }

        $transaction = array_values($transactions)[0];
        $payments = $this->helper->getOrderPayment($transaction->getPaymentId());
        $payment = array_values($payments)[0];

        if ($payment->getMethod() != 'dagcoin') {
            return;
        }

        $this->storeManager = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Magento\Store\Model\StoreManagerInterface');

        if (!$transaction->getIsClosed()) {
            $transaction->setIsClosed(1)->save();
            $payment->addTransactionCommentsToOrder(
                $transaction,
                "The transaction was canceled."
            );
            $payment->save();

            $client = $this->helper->getClient();
            $client->cancelInvoice($transaction->getTxnId());
        }
    }
}
