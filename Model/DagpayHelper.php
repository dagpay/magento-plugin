<?php

namespace Dagcoin\PaymentGateway\Model;

class DagpayHelper
{
    private $config;
    private $transactionRepository;
    private $paymentRepository;
    private $orderRepository;
    private $searchCriteriaBuilderFactory;
    private $clientFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Repository $paymentRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        \Dagcoin\PaymentGateway\lib\DagpayClientFactory $clientFactory
    )
    {
        $this->config = $context->getScopeConfig();
        $this->transactionRepository = $transactionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->clientFactory = $clientFactory;
    }

    public function getClient()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->clientFactory->create([
            'environment_id' => $this->config->getValue("payment/dagcoin/environment_id", $storeScope),
            'user_id' => $this->config->getValue("payment/dagcoin/user_id", $storeScope),
            'secret' => $this->config->getValue("payment/dagcoin/secret", $storeScope),
            'mode' => $this->config->getValue("payment/dagcoin/testmode", $storeScope),
            'platform' => 'standalone'
        ]);
    }

    private function getSearchCriteriaBuilder()
    {
        return $this->searchCriteriaBuilderFactory->create();
    }

    public function getTransactionsByOrderId($orderId)
    {
        $searchCriteria = $this->getSearchCriteriaBuilder()->addFilter('order_id', $orderId, 'eq')->create();
        return $this->transactionRepository->getList($searchCriteria)->getItems();
    }

    public function getTransactionByPaymentId($paymentId)
    {
        $searchCriteria = $this->getSearchCriteriaBuilder()->addFilter('payment_id', $paymentId, 'eq')->create();
        return array_values($this->transactionRepository->getList($searchCriteria)->getItems())[0];
    }

    public function getOrderPayment($paymentId)
    {
        $searchCriteria = $this->getSearchCriteriaBuilder()->addFilter('entity_id', $paymentId, 'eq')->create();
        return $this->paymentRepository->getList($searchCriteria)->getItems();
    }

    public function getPaymentById($paymentId)
    {
        return $this->paymentRepository->get($paymentId);
    }

    public function getOrderById($orderId)
    {
        $searchCriteria = $this->getSearchCriteriaBuilder()->addFilter('increment_id', $orderId, 'eq')->create();
        return array_values($this->orderRepository->getList($searchCriteria)->getItems())[0];
    }
}
