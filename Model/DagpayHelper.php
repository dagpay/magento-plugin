<?php

namespace Dagcoin\PaymentGateway\Model;

class DagpayHelper {
    private $transactionRepository;
    private $paymentRepository;
    private $orderRepository;

    public function __construct(
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Repository $paymentRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository
    )
    {
        $this->transactionRepository = $transactionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->orderRepository = $orderRepository;
    }

    public function get_transactions_by_order_id($orderId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $searchCriteriaBuilder = $objectManager->create('Magento\Framework\Api\SearchCriteriaBuilder');
        $searchCriteria = $searchCriteriaBuilder->addFilter('order_id', $orderId, 'eq')->create();
        return $this->transactionRepository->getList($searchCriteria)->getItems();
    }

    public function get_transaction_by_payment_id($paymentId) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $searchCriteriaBuilder = $objectManager->create('Magento\Framework\Api\SearchCriteriaBuilder');
        $searchCriteria = $searchCriteriaBuilder->addFilter('payment_id', $paymentId, 'eq')->create();
        return array_values($this->transactionRepository->getList($searchCriteria)->getItems())[0];
    }

    public function get_order_payment($paymentId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $searchCriteriaBuilder = $objectManager->create('Magento\Framework\Api\SearchCriteriaBuilder');
        $searchCriteria = $searchCriteriaBuilder->addFilter('entity_id', $paymentId, 'eq')->create();
        return $this->paymentRepository->getList($searchCriteria)->getItems();
    }

    public function get_payment_by_id($paymentId) {
        return $this->paymentRepository->get($paymentId);
    }

    public function get_order_by_id($orderId) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $searchCriteriaBuilder = $objectManager->create('Magento\Framework\Api\SearchCriteriaBuilder');
        $searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', $orderId, 'eq')->create();
        return array_values($this->orderRepository->getList($searchCriteria)->getItems())[0];
    }
}
