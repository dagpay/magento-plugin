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

class Index extends  \Magento\Framework\App\Action\Action
{
	protected $_objectmanager;
	protected $_checkoutSession;
	protected $_orderFactory;
	protected $urlBuilder;
	protected $response;
	protected $config;
	protected $messageManager;
	protected $helper;
	protected $cart;
	protected $inbox;
    protected $client;
	 
	public function __construct( Context $context,
			Session $checkoutSession,
			OrderFactory $orderFactory,
			ScopeConfigInterface $scopeConfig,
			Http $response,
			TransactionBuilder $tb,
			 \Magento\Checkout\Model\Cart $cart,
			 \Magento\AdminNotification\Model\Inbox $inbox,
             \Dagcoin\PaymentGateway\Model\DagpayHelper $helper,
             \Magento\Framework\Filesystem\DirectoryList $dir
		) {

      
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->response = $response;
        $this->config = $scopeConfig;
        $this->transactionBuilder = $tb;
        $this->cart = $cart;
        $this->inbox = $inbox;
        $this->helper = $helper;
		$this->urlBuilder = \Magento\Framework\App\ObjectManager::getInstance()
							->get('Magento\Framework\UrlInterface');
		
		parent::__construct($context);
    }

	public function execute()
	{
        $data = json_decode(file_get_contents("php://input"));

        $ds = DIRECTORY_SEPARATOR;
        require_once(__DIR__ . "$ds..$ds..$ds/lib/DagpayClient.php");
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $env_id = $this->config->getValue("payment/dagcoin/environment_id",$storeScope);
        $user_id = $this->config->getValue("payment/dagcoin/user_id",$storeScope);
        $secret = $this->config->getValue("payment/dagcoin/secret",$storeScope);
        $testmode = $this->config->getValue("payment/dagcoin/testmode",$storeScope);

        $signature = (new \DagpayClient($env_id, $user_id, $secret, $testmode))->get_invoice_info_signature($data);
        if ($signature != $data->signature)
            die();

        $payment = $this->helper->get_payment_by_id($data->paymentId);
        $transaction = $this->helper->get_transaction_by_payment_id($payment->getData('entity_id'));
        $order = $transaction->getOrder();

        switch ($data->state) {
//                case 'PENDING': // ignore
//                case 'WAITING_FOR_CONFIRMATION': // ignore
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
