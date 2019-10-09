<?php

namespace Dagcoin\PaymentGateway\Block;

use Dagcoin\PaymentGateway\Model\DagpayHelper;
use Exception;
use Magento\AdminNotification\Model\Inbox;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;

class Main extends Template
{
    public $checkoutSession;
    public $response;
    public $config;
    public $transactionBuilder;
    public $inbox;
    private $storeManager;
    private $helper;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        Http $response,
        TransactionBuilder $tb,
        Inbox $inbox,
        DagpayHelper $helper,
        StoreManagerInterface $storeManager
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->response = $response;
        $this->config = $context->getScopeConfig();
        $this->transactionBuilder = $tb;
        $this->inbox = $inbox;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function _prepareLayout()
    {
        $orderId = $this->checkoutSession->getLastOrderId();
        $order = ObjectManager::getInstance()->create('\Magento\Sales\Model\Order')->load($orderId);
        try {
            if ($order) {
                if ($order->getStatus() !== 'pending') {
                    $this->redirectToBase();
                    $this->setMessages(['Order cannot be paid.']);

                    return;
                }

                $payment = $order->getPayment();
                $client = $this->helper->getClient();

                $invoice = $client->create_invoice(
                    $payment->getData('entity_id'),
                    $order->getOrderCurrencyCode(),
                    $order->getGrandTotal()
                )->payload;

                $payment->setTransactionId($invoice->id);
                $payment->setAdditionalInformation([Transaction::RAW_DETAILS => ['Transaction is yet to complete']]);

                $trn = $payment->addTransaction(Transaction::TYPE_CAPTURE, null, true);
                $trn->setIsClosed(0)->save();
                $payment->addTransactionCommentsToOrder(
                    $trn,
                    'The transaction is yet to complete.'
                );

                $payment->setParentTransactionId(null);
                $payment->save();
                $order->save();

                $this->setAction($invoice->paymentUrl);
            } else {
                $this->redirectToBase();
            }
        } catch (Exception $e) {
            $this->setMessages(["Couldn't proceed with the payment... Please, refresh the page."]);
        }
    }

    private function redirectToBase()
    {
        $this->setAction($this->storeManager->getStore()->getBaseUrl());
    }
}
