<?php
namespace Dagcoin\PaymentGateway\Block;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;

class Main extends \Magento\Framework\View\Element\Template
{
    public $checkoutSession;
    public $orderFactory;
    public $response;
    public $config;
    public $transactionBuilder;
    public $inbox;
    private $storeManager;
    private $helper;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        Http $response,
        TransactionBuilder $tb,
        \Magento\AdminNotification\Model\Inbox $inbox,
        \Dagcoin\PaymentGateway\Model\DagpayHelper $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {

        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
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
        $method_data = [];
        $orderId = $this->checkoutSession->getLastOrderId();
        $order = $this->orderFactory->create()->load($orderId);

        try {
            if ($order) {
                $transactions = $this->helper->getTransactionsByOrderId($orderId);
                if (!empty($transactions)) {
                    $this->redirectToBase();
                    return;
                }

                $payment = $order->getPayment();
                $client = $this->helper->getClient();

                $invoice = $client->createInvoice(
                    $payment->getData('entity_id'),
                    $order->getOrderCurrencyCode(),
                    $order->getGrandTotal()
                )->payload;

                $payment->setTransactionId($invoice->id);
                $payment->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => ["Transaction is yet to complete"]]
                );

                $trn =
                    $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true);
                $trn->setIsClosed(0)->save();
                $payment->addTransactionCommentsToOrder(
                    $trn,
                    "The transaction is yet to complete."
                );

                $payment->setParentTransactionId(null);
                $payment->save();
                $order->save();

                $this->setAction($invoice->paymentUrl);
                $this->setMessages(isset($method_data['errors']) ? $method_data['errors'] : null);
            } else {
                $this->redirectToBase();
            }
        } catch (\Exception $e) {
            $method_data['errors'][] = "Couldn't proceed the payment... Please, refresh the page!";
        }
    }

    private function redirectToBase()
    {
        $this->setAction($this->storeManager->getStore()->getBaseUrl());
    }
}
