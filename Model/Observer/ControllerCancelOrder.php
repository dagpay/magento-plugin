<?php

namespace Dagcoin\PaymentGateway\Model\Observer;

use Dagcoin\PaymentGateway\Model\DagpayHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;

class ControllerCancelOrder implements ObserverInterface
{
    public $checkoutSession;
    public $orderFactory;
    private $helper;

    public function __construct(
        Session $checkoutSession,
        OrderFactory $orderFactory,
        DagpayHelper $helper
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer)
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
        if ($payment->getMethod() !== 'dagcoin') {
            return;
        }
        if (!$transaction->getIsClosed()) {
            $transaction->setIsClosed(1)->save();
            $payment->addTransactionCommentsToOrder(
                $transaction,
                'The transaction was canceled.'
            );
            $payment->save();

            $client = $this->helper->getClient();
            $client->cancel_invoice($transaction->getTxnId());
        }
    }
}
