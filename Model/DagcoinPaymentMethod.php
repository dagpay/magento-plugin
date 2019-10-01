<?php
// @codingStandardsIgnoreFile
namespace Dagcoin\PaymentGateway\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Payment\Model\Method\AbstractMethod;

class DagcoinPaymentMethod extends AbstractMethod
{
    public $_isInitializeNeeded = false;
    public $redirect_uri;
    public $_code = 'dagcoin';
    public $_canOrder = true;
    public $_isGateway = true;

    public function getOrderPlaceRedirectUrl()
    {
        return ObjectManager::getInstance()->get('Magento\Framework\UrlInterface')->getUrl('dagcoin/redirect');
    }
}
