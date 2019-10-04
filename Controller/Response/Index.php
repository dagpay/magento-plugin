<?php

namespace Dagcoin\PaymentGateway\Controller\Response;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;
use Magento\Sales\Model\Order\Payment\Transaction;

class Index extends \Magento\Framework\App\Action\Action
{
    public $checkoutSession;
    public $orderFactory;
    public $response;
    public $helper;
    public $cart;
    public $inbox;
    public $driver;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        Http $response,
        TransactionBuilder $tb,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\AdminNotification\Model\Inbox $inbox,
        \Dagcoin\PaymentGateway\Model\DagpayHelper $helper,
        \Magento\Framework\Filesystem\Driver $driver
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->response = $response;
        $this->transactionBuilder = $tb;
        $this->cart = $cart;
        $this->inbox = $inbox;
        $this->helper = $helper;
        $this->driver = $driver;

        parent::__construct($context);
    }

    public function execute()
    {
        $data = json_decode($this->driver->fileGetContents("php://input"));

        $client = $this->helper->getClient();
        $signature = $client->getInvoiceInfoSignature($data);
        if ($signature != $data->signature) {
            return;
        }

        $payment = $this->helper->getPaymentById($data->paymentId);
        $transaction = $this->helper->getTransactionByPaymentId($payment->getData('entity_id'));
        $order = $transaction->getOrder();

        switch ($data->state) {
            case 'PAID':
            case 'PAID_EXPIRED':
                $payment->addTransactionCommentsToOrder(
                    $transaction,
                    "The transaction is paid."
                );
                $transaction->setIsClosed(1)->save();
                $payment->setAmountPaid($data->currencyAmount);
                $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                break;
            case 'CANCELLED':
                $order->cancel();
                break;
            case 'EXPIRED':
                $transaction->setIsClosed(1)->save();
                $order->cancel();
                $payment->addTransactionCommentsToOrder(
                    $transaction,
                    "The transaction has expired."
                );
                break;
            case 'FAILED':
                $transaction->setIsClosed(1)->save();
                $order->cancel();
                $payment->addTransactionCommentsToOrder(
                    $transaction,
                    "The transaction has failed."
                );
                break;
        }

        $payment->save();
        $order->save();
    }
}
