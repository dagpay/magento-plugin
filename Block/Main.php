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

class Main extends  \Magento\Framework\View\Element\Template
{
	 protected $_objectmanager;
	 protected $checkoutSession;
	 protected $orderFactory;
	 protected $urlBuilder;
	 protected $response;
	 protected $config;
	 protected $messageManager;
	 protected $transactionBuilder;
	 protected $inbox;
	 private $storeManager;
	 protected $helper;
	 public function __construct(Context $context,
			Session $checkoutSession,
			OrderFactory $orderFactory,
			Http $response,
			TransactionBuilder $tb,
			 \Magento\AdminNotification\Model\Inbox $inbox,
             \Dagcoin\PaymentGateway\Model\DagpayHelper $helper
		) {

        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->response = $response;
        $this->config = $context->getScopeConfig();
        $this->transactionBuilder = $tb;
		$this->inbox = $inbox;
		$this->helper = $helper;

         $ds = DIRECTORY_SEPARATOR;
         require_once(__DIR__ . "$ds..$ds/lib/DagpayClient.php");
         $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
         $env_id = $this->config->getValue("payment/dagcoin/environment_id",$storeScope);
         $user_id = $this->config->getValue("payment/dagcoin/user_id",$storeScope);
         $secret = $this->config->getValue("payment/dagcoin/secret",$storeScope);
         $testmode = $this->config->getValue("payment/dagcoin/testmode",$storeScope);

         $this->client = new \DagpayClient($env_id, $user_id, $secret, $testmode);
        
		$this->urlBuilder = \Magento\Framework\App\ObjectManager::getInstance()
							->get('Magento\Framework\UrlInterface');

		$this->storeManager = \Magento\Framework\App\ObjectManager::getInstance()
                            ->get('\Magento\Store\Model\StoreManagerInterface');

		parent::__construct($context);
    }

	protected function _prepareLayout()
	{
        $method_data = array();
        $orderId = $this->checkoutSession->getLastOrderId();
		$order = $this->orderFactory->create()->load($orderId);

		try {
            if ($order) {
                $transactions = $this->helper->get_transactions_by_order_id($orderId);
                if (count($transactions) !== 0) {
                    $this->redirect_to_base();
                    return;
                }

                $payment = $order->getPayment();

                $invoice = $this->client->create_invoice(
                    $payment->getData('entity_id'),
                    $order->getOrderCurrencyCode(),
                    $order->getGrandTotal()
                )->payload;

                $payment->setTransactionId($invoice->id);
                $payment->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array("Transaction is yet to complete")]
                );

                $trn = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true);
                $trn->setIsClosed(0)->save();
                $payment->addTransactionCommentsToOrder(
                    $trn,
                    "The transaction is yet to complete."
                );

                $payment->setParentTransactionId(null);
                $payment->save();
                $order->save();

                $this->setAction($invoice->paymentUrl);
                $this->setMessages($method_data['errors']);
            } else {
                $this->redirect_to_base();
            }
        } catch (DagpayException $e) {
            $method_data['errors'][] = $e->getMessage();
        } catch (\Exception $e) {
            $method_data['errors'][] = "Couldn't proceed the payment... Please, refresh the page!";
        }
	}

	private function redirect_to_base() {
        $this->setAction($this->storeManager->getStore()->getBaseUrl());
    }
}
