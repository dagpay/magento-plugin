<?php

namespace Dagcoin\PaymentGateway\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\View\Element\Template\Context;

class ControllerCancelOrder implements ObserverInterface
{
    protected $checkoutSession;
    protected $orderFactory;
    protected $config;
    protected $helper;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        \Dagcoin\PaymentGateway\Model\DagpayHelper $helper
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->helper = $helper;

        $this->config = $context->getScopeConfig();
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getData('order');

        if (!$order)
            return;

        $transactions = $this->helper->get_transactions_by_order_id($order->getId());
        if (count($transactions) === 0)
            return;

        $transaction = array_values($transactions)[0];
        $payments = $this->helper->get_order_payment($transaction->getPaymentId());
        $payment = array_values($payments)[0];

        if ($payment->getMethod() != 'dagcoin')
            return;

        $this->storeManager = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Magento\Store\Model\StoreManagerInterface');

        if (!$transaction->getIsClosed()) {
            $transaction->setIsClosed(1)->save();
            $payment->addTransactionCommentsToOrder(
                $transaction,
                "The transaction was canceled."
            );
            $payment->save();

            $ds = DIRECTORY_SEPARATOR;
            require_once(__DIR__ . "$ds..$ds..$ds/lib/DagpayClient.php");
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $env_id = $this->config->getValue("payment/dagcoin/environment_id", $storeScope);
            $user_id = $this->config->getValue("payment/dagcoin/user_id", $storeScope);
            $secret = $this->config->getValue("payment/dagcoin/secret", $storeScope);
            $testmode = $this->config->getValue("payment/dagcoin/testmode", $storeScope);
            $client = new \DagpayClient($env_id, $user_id, $secret, $testmode);
            $client->cancel_invoice($payment->getLastTransId());
        }
    }
}