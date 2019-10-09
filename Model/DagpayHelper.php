<?php

namespace Dagcoin\PaymentGateway\Model;

use Dagpay\DagpayClient;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Payment\Repository;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as TransactionRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\ScopeInterface;

class DagpayHelper
{
    private $config;
    private $transactionRepository;
    private $paymentRepository;
    private $orderRepository;
    private $searchCriteriaBuilderFactory;

    public function __construct(
        Context $context,
        TransactionRepository $transactionRepository,
        Repository $paymentRepository,
        OrderRepository $orderRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    )
    {
        $this->config = $context->getScopeConfig();
        $this->transactionRepository = $transactionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    public function getClient()
    {
        $storeScope = ScopeInterface::SCOPE_STORE;

        return new DagpayClient(
            $this->config->getValue('payment/dagcoin/environment_id', $storeScope),
            $this->config->getValue('payment/dagcoin/user_id', $storeScope),
            $this->config->getValue('payment/dagcoin/secret', $storeScope),
            $this->config->getValue('payment/dagcoin/testmode', $storeScope),
            'standalone'
        );
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
