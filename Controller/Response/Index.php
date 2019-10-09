<?php

namespace Dagcoin\PaymentGateway\Controller\Response;

use Dagcoin\PaymentGateway\Model\DagpayHelper;
use Magento\AdminNotification\Model\Inbox;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;
use Magento\Framework\Filesystem\Driver\File;

class Index extends Action
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
        Quote $quote,
        Inbox $inbox,
        DagpayHelper $helper,
        File $driver
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->response = $response;
        $this->transactionBuilder = $tb;
        $this->quote = $quote;
        $this->inbox = $inbox;
        $this->helper = $helper;
        $this->driver = $driver;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $data = json_decode($this->driver->fileGetContents('php://input'));
        $client = $this->helper->getClient();
        $signature = $client->get_invoice_info_signature($data);
        if ($signature !== $data->signature) {
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
                    'The transaction is paid.'
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
                    'The transaction has expired.'
                );

                break;
            case 'FAILED':
                $transaction->setIsClosed(1)->save();
                $order->cancel();
                $payment->addTransactionCommentsToOrder(
                    $transaction,
                    'The transaction has failed.'
                );

                break;
        }

        $payment->save();
        $order->save();
        return 'ok';
    }
}
